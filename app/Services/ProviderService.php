<?php
/**
 * Provider Orchestration Service
 * 
 * Instantiates provider adapters, synchronizes catalogs,
 * and monitors provider health status.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Logger;
use Modules\Providers\ProviderInterface;
use Modules\Providers\SMSPoolProvider;
use Modules\Providers\DaisySMSProvider;
use Modules\Providers\MockTestingProvider;
use Exception;

class ProviderService {
    /**
     * Resolve the active provider adapter instance
     */
    public static function getActiveProvider(?string $code = null): ProviderInterface {
        $providerCode = $code ?? env('OTP_PROVIDER_DEFAULT', 'smspool');

        return match ($providerCode) {
            'smspool' => new SMSPoolProvider(),
            'daisysms' => new DaisySMSProvider(),
            'mock_test' => new MockTestingProvider(),
            default => new SMSPoolProvider(),
        };
    }

    /**
     * Synchronize services and countries from provider to database
     */
    public static function syncCatalog(ProviderInterface $provider): array {
        $startTime = microtime(true);
        $providerCode = $provider->getCode();

        // 1. Ensure provider record exists in database
        $dbProvider = Database::queryOne("SELECT id FROM providers WHERE code = :code LIMIT 1", [':code' => $providerCode]);
        if (!$dbProvider) {
            Database::execute("
                INSERT INTO providers (code, name, api_url, is_active, created_at, updated_at)
                VALUES (:code, :name, 'configured_via_env', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ", [
                ':code' => $providerCode,
                ':name' => $provider->getName(),
            ]);
            $providerId = (int) Database::lastInsertId();
        } else {
            $providerId = (int) $dbProvider['id'];
        }

        $itemsSynced = 0;
        $itemsUpdated = 0;

        try {
            // Sync Countries
            $countries = $provider->fetchCountries();
            foreach ($countries as $c) {
                $code = strtoupper($c['code']);
                $name = $c['name'];
                $dial = $c['dial_code'];

                $existingCountry = Database::queryOne("SELECT id FROM countries WHERE iso_code = :code LIMIT 1", [':code' => $code]);
                if (!$existingCountry) {
                    Database::execute("
                        INSERT INTO countries (iso_code, name, dial_code, is_active, created_at, updated_at)
                        VALUES (:code, :name, :dial, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ", [':code' => $code, ':name' => $name, ':dial' => $dial]);
                }
            }

            // Sync Services
            $services = $provider->fetchServices();
            foreach ($services as $s) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $s['name']));
                $name = $s['name'];
                $cost = (float) $s['cost'];

                // Check or insert master service
                $existingService = Database::queryOne("SELECT id, default_selling_price FROM services WHERE slug = :slug LIMIT 1", [':slug' => $slug]);
                if (!$existingService) {
                    // Set default markup price (cost * 1.5 rounded to 2 decimals)
                    $defaultPrice = max(1.00, round($cost * 1.5, 2));
                    Database::execute("
                        INSERT INTO services (slug, name, category, default_selling_price, is_active, created_at, updated_at)
                        VALUES (:slug, :name, 'Social Media', :price, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ", [
                        ':slug' => $slug,
                        ':name' => $name,
                        ':price' => $defaultPrice,
                    ]);
                    $serviceId = (int) Database::lastInsertId();
                    $itemsSynced++;
                } else {
                    $serviceId = (int) $existingService['id'];
                    $itemsUpdated++;
                }

                // Associate in provider_services for default countries
                $countryList = Database::query("SELECT id FROM countries WHERE is_active = 1 LIMIT 5");
                foreach ($countryList as $countryRow) {
                    $cid = (int) $countryRow['id'];
                    $existingPivot = Database::queryOne("
                        SELECT id FROM provider_services 
                        WHERE provider_id = :pid AND service_id = :sid AND country_id = :cid
                    ", [':pid' => $providerId, ':sid' => $serviceId, ':cid' => $cid]);

                    if (!$existingPivot) {
                        Database::execute("
                            INSERT INTO provider_services (
                                provider_id, service_id, country_id, provider_service_code,
                                provider_cost, selling_price, stock_status, is_active, created_at, updated_at
                            ) VALUES (
                                :pid, :sid, :cid, :code, :cost, :selling, 'available', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                            )
                        ", [
                            ':pid' => $providerId,
                            ':sid' => $serviceId,
                            ':cid' => $cid,
                            ':code' => $s['code'],
                            ':cost' => $cost,
                            ':selling' => max(1.20, round($cost * 1.4, 2)),
                        ]);
                    }
                }
            }

            // Update Provider sync timestamp
            Database::execute("UPDATE providers SET last_synced_at = CURRENT_TIMESTAMP, is_healthy = 1 WHERE id = :id", [':id' => $providerId]);

            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log to sync_logs
            Database::execute("
                INSERT INTO sync_logs (provider_id, sync_type, items_synced, items_updated, status, execution_time_ms, created_at)
                VALUES (:pid, 'services', :synced, :updated, 'success', :time, CURRENT_TIMESTAMP)
            ", [
                ':pid' => $providerId,
                ':synced' => $itemsSynced,
                ':updated' => $itemsUpdated,
                ':time' => $executionTimeMs,
            ]);

            return [
                'success' => true,
                'items_synced' => $itemsSynced,
                'items_updated' => $itemsUpdated,
                'execution_time_ms' => $executionTimeMs,
            ];
        } catch (Exception $e) {
            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            Database::execute("
                INSERT INTO sync_logs (provider_id, sync_type, status, error_message, execution_time_ms, created_at)
                VALUES (:pid, 'services', 'failed', :err, :time, CURRENT_TIMESTAMP)
            ", [
                ':pid' => $providerId,
                ':err' => $e->getMessage(),
                ':time' => $executionTimeMs,
            ]);

            Logger::error('provider', "Sync failed: " . $e->getMessage());
            throw $e;
        }
    }
}
