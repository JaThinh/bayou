<?php
/**
 * Utility: Currency Converter
 * Chuyển đổi tỷ giá tiền tệ động
 */
namespace App\Utils;

use App\Core\Database;

class CurrencyConverter
{
    private const USD_RATE_CACHE_TTL_SECONDS = 12 * 60 * 60;
    private const VIETCOMBANK_EXCHANGE_RATE_URL = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx';

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) return $amount;
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT rate FROM exchange_rates WHERE from_currency = :from AND to_currency = :to");
        $stmt->execute([':from' => $from, ':to' => $to]);
        $row = $stmt->fetch();
        
        if (!$row) return $amount;
        
        return round($amount * $row['rate'], 2);
    }

    public static function getLatestUsdRate(): float
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT rate, fetched_at
             FROM exchange_rates
             WHERE from_currency = :from AND to_currency = :to
             LIMIT 1"
        );
        $stmt->execute([':from' => 'USD', ':to' => 'VND']);
        $cachedRate = $stmt->fetch();

        if ($cachedRate && self::isFreshRate($cachedRate['fetched_at'])) {
            return (float) $cachedRate['rate'];
        }

        try {
            $rate = self::fetchVietcombankUsdSellRate();
            self::saveUsdRate($db, $rate);

            return $rate;
        } catch (\Throwable $e) {
            if ($cachedRate) {
                return (float) $cachedRate['rate'];
            }

            throw $e;
        }
    }

    private static function isFreshRate(?string $fetchedAt): bool
    {
        if (!$fetchedAt) {
            return false;
        }

        $timestamp = strtotime($fetchedAt);
        if ($timestamp === false) {
            return false;
        }

        return $timestamp >= time() - self::USD_RATE_CACHE_TTL_SECONDS;
    }

    private static function fetchVietcombankUsdSellRate(): float
    {
        $xmlString = self::fetchVietcombankXml();
        $previousXmlErrorHandling = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $isLoaded = $document->loadXML($xmlString);
        libxml_clear_errors();
        libxml_use_internal_errors($previousXmlErrorHandling);

        if (!$isLoaded) {
            throw new \RuntimeException('Unable to parse Vietcombank exchange rate XML.');
        }

        foreach ($document->getElementsByTagName('Exrate') as $exchangeRate) {
            if (!$exchangeRate instanceof \DOMElement) {
                continue;
            }

            if (strtoupper($exchangeRate->getAttribute('CurrencyCode')) !== 'USD') {
                continue;
            }

            $rawSellRate = trim($exchangeRate->getAttribute('Sell'));
            $normalizedRate = str_replace(',', '', $rawSellRate);
            $rate = (float) $normalizedRate;

            if ($rate <= 0) {
                throw new \RuntimeException('Invalid USD sell rate from Vietcombank XML.');
            }

            return $rate;
        }

        throw new \RuntimeException('Unable to find USD sell rate in Vietcombank XML.');
    }

    private static function fetchVietcombankXml(): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init(self::VIETCOMBANK_EXCHANGE_RATE_URL);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'BayouOTA/1.0',
            ]);

            $response = curl_exec($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($response !== false && $statusCode >= 200 && $statusCode < 300) {
                return $response;
            }

            throw new \RuntimeException('Unable to fetch Vietcombank exchange rate XML: ' . $error);
        }

        $response = file_get_contents(self::VIETCOMBANK_EXCHANGE_RATE_URL);
        if ($response === false) {
            throw new \RuntimeException('Unable to fetch Vietcombank exchange rate XML.');
        }

        return $response;
    }

    private static function saveUsdRate(\PDO $db, float $rate): void
    {
        $stmt = $db->prepare(
            "INSERT INTO exchange_rates (from_currency, to_currency, rate, fetched_at)
             VALUES (:from, :to, :rate, NOW())
             ON DUPLICATE KEY UPDATE rate = VALUES(rate), fetched_at = NOW()"
        );
        $stmt->execute([
            ':from' => 'USD',
            ':to' => 'VND',
            ':rate' => $rate,
        ]);
    }

    public static function formatVND(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . '₫';
    }

    public static function formatUSD(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',');
    }
}
