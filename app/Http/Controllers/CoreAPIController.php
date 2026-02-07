<?php

namespace App\Http\Controllers;

use App\Models\Masters\Employee;
use App\Models\Masters\EmployeeReporting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Log;

class CoreAPIController extends Controller
{
    public function index()
    {
        $api_list = DB::table('core_apis')->get();
        return view('core.api.index', compact('api_list'));
    }

    public function sync()
    {
        try {
            // Retrieve the API key and base URL
            $apiData = DB::table('core_api_setup')->first();
            $apiKey = $apiData->api_key;
            $baseUrl = $apiData->base_url;

            // Make the GET request with the correct headers
            $response = Http::withHeaders([
                'api-key' => $apiKey, // Setting the 'api-key' header as required
                'Accept' => 'application/json',
            ])->get("$baseUrl/api/project/apis");

            // Check if the response is successful
            if ($response->failed()) {
                // Handle unsuccessful responses
                Log::error('API sync failed', ['status' => $response->status(), 'response' => $response->body()]);
                return response()->json(['status' => 400, 'msg' => 'Failed to synchronize APIs.']);
            }

            // Parse the JSON response
            $data = $response->json();

            // Validate the structure of the response
            if (!isset($data['api_list']) || !is_array($data['api_list'])) {
                Log::error('Invalid API response structure', ['response' => $data]);
                return response()->json(['status' => 400, 'msg' => 'Unexpected API response format.']);
            }

            // Prepare data for batch insertion
            $apiRecords = array_map(function ($value) {
                return [
                    'api_id' => $value['id'] ?? null,
                    'api_name' => $value['api_name'] ?? '',
                    'api_end_point' => $value['api_end_point'] ?? '',
                    'description' => $value['description'] ?? '',
                    'parameters' => $value['parameters'],
                    'table_name' => $value['table_name'] ?? '',
                ];
            }, $data['api_list']);

            // Use a transaction to ensure atomic operation

            DB::table('core_apis')->truncate();
            DB::table('core_apis')->insert($apiRecords); // Batch insert for performance


            return response()->json(['status' => 200, 'msg' => 'API synchronized successfully.']);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-specific exceptions
            Log::error('Database error during API sync', ['error' => $e->getMessage()]);
            return response()->json(['status' => 500, 'msg' => 'Database error occurred.']);
        }
    }

    public function importAPISData(Request $request)
    {
        // Increase execution time and memory limit
        ini_set('max_execution_time', 0); // UNLIMITED
        set_time_limit(0); // UNLIMITED
        ini_set('memory_limit', '2048M');

        $apiEndPoints = $request->input('api_end_points');
        $CoreAPI = DB::table('core_api_setup')->first();

        if (!$CoreAPI) {
            return response()->json(['status' => 400, 'msg' => 'API configuration not found']);
        }

        $apiKey = $CoreAPI->api_key;
        $baseUrl = $CoreAPI->base_url;
        $prefix = 'core_';

        // Sort APIs by dependency
        $sortedApis = $this->sortApisByDependency($apiEndPoints);

        $results = [];
        foreach ($sortedApis as $api) {
            Log::info("Starting sync for API: $api");

            try {
                $apiData = DB::table('core_apis')->where('api_end_point', $api)->first(['parameters', 'table_name']);

                if (!$apiData) {
                    Log::error("API configuration not found for: $api");
                    $results[$api] = ['status' => 'error', 'message' => 'API configuration not found'];
                    continue;
                }

                $parameter = $apiData->parameters ?? null;
                $tableName = $prefix . $apiData->table_name;

                if ($api === 'employee_tools') {
                    $applicationName = 'Consultancy HRIMS';
                    $response = Http::withHeaders([
                        'api-key' => $apiKey,
                        'Accept' => 'application/json',
                    ])->get("$baseUrl/api/$api", ['application_name' => $applicationName]);

                    if ($response->failed()) {
                        Log::error("API sync failed for $api: HTTP Status {$response->status()}");
                        continue;
                    }

                    $data = $response->json();
                    if (!isset($data['list']) || !is_array($data['list'])) {
                        Log::error("Invalid API response structure for $api");
                        continue;
                    }

                    // Get ALL employee IDs from API response (regardless of status)
                    $apiEmployeeIds = array_column($data['list'], 'employee_id');

                    // Get existing employee IDs from our database
                    $existingEmployeeIds = DB::table('users')
                        ->whereNotNull('emp_id')
                        ->pluck('emp_id')
                        ->toArray();

                    // Find employees in DB but not in API response at all
                    $missingEmployeeIds = array_diff($existingEmployeeIds, $apiEmployeeIds);

                    // Disable completely missing employees
                    if (!empty($missingEmployeeIds)) {
                        DB::table('users')
                            ->whereIn('emp_id', $missingEmployeeIds)
                            ->update([
                                'status' => 'D',
                                'updated_at' => now()
                            ]);
                    }

                    // Process all employees from API (both active and inactive)
                    foreach ($data['list'] as $employee) {
                        try {
                            $userData = [
                                'name' => $employee['emp_name'] ?? '',
                                'emp_id' => $employee['employee_id'],
                                'emp_code' => $employee['emp_code'],
                                'status' => 'A',
                                'reporting_id' => $employee['emp_reporting'],                               
                                'email' => $employee['emp_email'] ?? null,
                                'password' => Hash::make($employee['emp_contact'] ?? 'default123'),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Only set created_at for new records
                            DB::table('users')->updateOrInsert(
                                ['emp_id' => $employee['employee_id']],
                                $userData
                            );
                        } catch (\Throwable $e) {
                            Log::error("Failed processing employee {$employee['employee_id']}: {$e->getMessage()}");
                        }
                    }

                    continue;
                }


                if ($parameter) {
                    $result = $this->processParameterBasedApi($api, $parameter, $tableName, $apiKey, $baseUrl);
                } else {
                    $result = $this->processSimpleApi($api, $tableName, $apiKey, $baseUrl);
                }

                $results[$api] = $result;
            } catch (\Exception $e) {
                Log::error("Error processing API $api: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                $results[$api] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        // Check if any API failed
        $hasErrors = false;
        foreach ($results as $api => $result) {
            if (isset($result['status']) && $result['status'] === 'error') {
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            return response()->json([
                'status' => 207, // 207 Multi-Status
                'msg' => 'Some APIs synchronized with errors',
                'results' => $results
            ]);
        }

        return response()->json(['status' => 200, 'msg' => 'All APIs synchronized successfully', 'results' => $results]);
    }

    private function processSimpleApi($api, $tableName, $apiKey, $baseUrl)
    {
        Log::info("Processing simple API: $api");

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("$baseUrl/api/$api");

            if ($response->failed()) {
                Log::error("API call failed for $api: " . $response->status() . " - " . $response->body());
                return ['status' => 'error', 'message' => "HTTP Error: " . $response->status()];
            }

            $data = $response->json();

            // Check response structure
            if (!isset($data['list'])) {
                Log::error("Invalid response structure for $api - missing 'list' key");
                Log::error("Response: " . json_encode($data));
                return ['status' => 'error', 'message' => 'Invalid response structure: missing list'];
            }

            if (!is_array($data['list'])) {
                Log::error("Invalid response structure for $api - list is not array");
                return ['status' => 'error', 'message' => 'Invalid response structure: list is not array'];
            }

            if (empty($data['list'])) {
                Log::warning("No data received for $api");
                return ['status' => 'success', 'message' => 'No data to sync', 'count' => 0];
            }

            // Create table
            $firstItem = $data['list'][0];
            if (!is_array($firstItem) || empty($firstItem)) {
                Log::error("First item in list is invalid for $api");
                return ['status' => 'error', 'message' => 'Invalid data structure in list'];
            }

            $columns = array_keys($firstItem);
            $this->createTableIfNotExists($tableName, $columns);

            // Insert data
            $insertedCount = 0;
            $updatedCount = 0;

            foreach ($data['list'] as $item) {
                if (!isset($item['id'])) {
                    Log::warning("Item missing 'id' field in $api, skipping");
                    continue;
                }

                $existing = DB::table($tableName)->where('id', $item['id'])->exists();

                if ($existing) {
                    DB::table($tableName)->where('id', $item['id'])->update($item);
                    $updatedCount++;
                } else {
                    DB::table($tableName)->insert($item);
                    $insertedCount++;
                }
            }

            Log::info("Completed $api: Inserted: $insertedCount, Updated: $updatedCount");
            return [
                'status' => 'success',
                'message' => "Synced successfully",
                'count' => count($data['list']),
                'inserted' => $insertedCount,
                'updated' => $updatedCount
            ];
        } catch (\Exception $e) {
            Log::error("Exception in processSimpleApi for $api: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function processParameterBasedApi($api, $parameter, $tableName, $apiKey, $baseUrl)
    {
        Log::info("Processing parameter-based API: $api with parameter: $parameter");

        try {
            // First, try to get table structure by testing the API
            $tableStructure = $this->getTableStructureFromApi($api, $parameter, $apiKey, $baseUrl);

            if (!$tableStructure) {
                Log::error("Failed to get table structure for $api");
                return ['status' => 'error', 'message' => 'Failed to get table structure from API'];
            }

            // Create table with the structure
            $this->createTableIfNotExists($tableName, $tableStructure['columns']);

            // Try to get all data at once first
            $bulkResult = $this->tryBulkFetch($api, $tableName, $apiKey, $baseUrl);
            if ($bulkResult['success']) {
                return $bulkResult;
            }

            Log::info("Bulk fetch failed, trying parameter-based fetching for $api");

            // Get source IDs for the parameter
            $ids = $this->getIdsForParameter($parameter);

            if ($ids->isEmpty()) {
                Log::warning("No source IDs found for parameter: $parameter");
                return [
                    'status' => 'warning',
                    'message' => "No source data found for parameter: $parameter",
                    'action' => 'Please sync parent tables first'
                ];
            }

            Log::info("Found " . $ids->count() . " source IDs for $api");

            // Process IDs in batches
            return $this->processIdsInBatches($api, $parameter, $tableName, $ids, $apiKey, $baseUrl);
        } catch (\Exception $e) {
            Log::error("Exception in processParameterBasedApi for $api: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getTableStructureFromApi($api, $parameter, $apiKey, $baseUrl)
    {
        Log::info("Getting table structure for $api");

        // Try multiple approaches to get structure
        $approaches = [
            // Approach 1: Try with parameter = 1
            ['method' => 'with_param', 'param_value' => 1],
            // Approach 2: Try with parameter = 0
            ['method' => 'with_param', 'param_value' => 0],
            // Approach 3: Try without parameter
            ['method' => 'without_param'],
        ];

        foreach ($approaches as $approach) {
            try {
                if ($approach['method'] === 'with_param') {
                    $response = Http::withHeaders([
                        'api-key' => $apiKey,
                        'Accept' => 'application/json',
                    ])->timeout(15)->get("$baseUrl/api/$api", [$parameter => $approach['param_value']]);
                } else {
                    $response = Http::withHeaders([
                        'api-key' => $apiKey,
                        'Accept' => 'application/json',
                    ])->timeout(15)->get("$baseUrl/api/$api");
                }

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['list']) && is_array($data['list']) && !empty($data['list'])) {
                        $firstItem = $data['list'][0];
                        if (is_array($firstItem) && !empty($firstItem)) {
                            return [
                                'columns' => array_keys($firstItem),
                                'sample_data' => $firstItem
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Approach failed for $api: " . $e->getMessage());
                continue;
            }
        }

        // If all approaches fail, try to guess columns based on parameter name
        Log::warning("Could not determine table structure for $api, using default columns");

        $defaultColumns = ['id', 'name', 'code', 'status'];

        // Add parameter column if it's a foreign key
        if (str_ends_with($parameter, '_id')) {
            $defaultColumns[] = $parameter;
        }

        return [
            'columns' => $defaultColumns,
            'sample_data' => null
        ];
    }

    private function tryBulkFetch($api, $tableName, $apiKey, $baseUrl)
    {
        Log::info("Attempting bulk fetch for $api");

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("$baseUrl/api/$api");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['list']) && is_array($data['list']) && !empty($data['list'])) {
                    $firstItem = $data['list'][0];
                    if (is_array($firstItem) && !empty($firstItem)) {
                        $columns = array_keys($firstItem);
                        $this->createTableIfNotExists($tableName, $columns);

                        $insertedCount = 0;
                        $updatedCount = 0;

                        foreach ($data['list'] as $item) {
                            if (!isset($item['id'])) {
                                continue;
                            }

                            $existing = DB::table($tableName)->where('id', $item['id'])->exists();

                            if ($existing) {
                                DB::table($tableName)->where('id', $item['id'])->update($item);
                                $updatedCount++;
                            } else {
                                DB::table($tableName)->insert($item);
                                $insertedCount++;
                            }
                        }

                        Log::info("Bulk sync successful for $api: Inserted: $insertedCount, Updated: $updatedCount");
                        return [
                            'success' => true,
                            'status' => 'success',
                            'message' => "Bulk sync completed",
                            'count' => count($data['list']),
                            'inserted' => $insertedCount,
                            'updated' => $updatedCount
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Bulk fetch failed for $api: " . $e->getMessage());
        }

        return ['success' => false];
    }

    private function processIdsInBatches($api, $parameter, $tableName, $ids, $apiKey, $baseUrl)
    {
        $batchSize = 50; // Smaller batch size for stability
        $idBatches = $ids->chunk($batchSize);
        $totalBatches = count($idBatches);

        $totalProcessed = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $failedIds = [];

        Log::info("Starting batch processing for $api with $totalBatches batches");

        foreach ($idBatches as $batchIndex => $batchIds) {
            $batchNumber = $batchIndex + 1;
            Log::info("Processing batch $batchNumber/$totalBatches for $api");

            // Process each ID in the batch
            foreach ($batchIds as $id) {
                try {
                    $response = Http::withHeaders([
                        'api-key' => $apiKey,
                        'Accept' => 'application/json',
                    ])->timeout(20)->get("$baseUrl/api/$api", [$parameter => $id]);

                    $totalProcessed++;

                    if ($response->successful()) {
                        $data = $response->json();

                        if (isset($data['list']) && is_array($data['list']) && !empty($data['list'])) {
                            $batchResult = $this->processApiBatch($tableName, $data['list']);
                            $totalInserted += $batchResult['inserted'];
                            $totalUpdated += $batchResult['updated'];
                        } else {
                            Log::warning("Empty list for $api with $parameter=$id");
                        }
                    } else {
                        Log::warning("Failed to fetch $api with $parameter=$id - Status: " . $response->status());
                        $failedIds[] = $id;
                    }
                } catch (\Exception $e) {
                    Log::error("Exception fetching $api with $parameter=$id: " . $e->getMessage());
                    $failedIds[] = $id;
                }

                // Log progress every 10 records
                if ($totalProcessed % 10 === 0) {
                    Log::info("Progress: Processed $totalProcessed/" . $ids->count() . " for $api");
                }

                // Small delay between requests
                usleep(100000); // 0.2 second delay
            }

            // Log batch completion
            Log::info("Completed batch $batchNumber/$totalBatches for $api");

            // Add delay between batches
            if ($batchNumber < $totalBatches) {
                sleep(0.5); // Reduced from 1 second to 0.5 seconds
            }
        }

        // Prepare result
        $result = [
            'status' => 'success',
            'message' => "Parameter-based sync completed",
            'total_processed' => $totalProcessed,
            'inserted' => $totalInserted,
            'updated' => $totalUpdated
        ];

        if (!empty($failedIds)) {
            $result['status'] = 'warning';
            $result['message'] = "Sync completed with some failures";
            $result['failed_count'] = count($failedIds);
            $result['failed_ids'] = array_slice($failedIds, 0, 10); // Show first 10 failed IDs
        }

        Log::info("Completed parameter-based sync for $api: " .
            "Total: $totalProcessed, " .
            "Inserted: $totalInserted, " .
            "Updated: $totalUpdated, " .
            "Failed: " . count($failedIds));

        return $result;
    }

    private function processApiBatch($tableName, $items)
    {
        $inserted = 0;
        $updated = 0;

        foreach ($items as $item) {
            if (!isset($item['id'])) {
                Log::warning("Item missing 'id' field, skipping");
                continue;
            }

            try {
                $existing = DB::table($tableName)->where('id', $item['id'])->exists();

                if ($existing) {
                    DB::table($tableName)->where('id', $item['id'])->update($item);
                    $updated++;
                } else {
                    DB::table($tableName)->insert($item);
                    $inserted++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to process item in $tableName: " . $e->getMessage());
            }
        }

        return ['inserted' => $inserted, 'updated' => $updated];
    }

    private function getIdsForParameter($parameter)
    {
        $parameterMap = [
            'country_id' => 'core_country',
            'state_id' => 'core_state',
            'district_id' => 'core_district',
            'city_id' => 'core_city',
            'village_id' => 'core_village',
            'region_id' => 'core_region',
            'zone_id' => 'core_zone',
            'area_id' => 'core_area',
            'parent_id' => 'core_' . str_replace('_id', '', $parameter),
        ];

        foreach ($parameterMap as $paramName => $tableName) {
            if ($parameter === $paramName || str_contains($parameter, $paramName)) {
                try {
                    if (DB::getSchemaBuilder()->hasTable($tableName)) {
                        $ids = DB::table($tableName)->pluck('id');
                        if ($ids->isNotEmpty()) {
                            Log::info("Found IDs in $tableName for parameter $parameter: " . $ids->count() . " records");
                            return $ids;
                        } else {
                            Log::warning("Table $tableName exists but is empty for parameter $parameter");
                        }
                    } else {
                        Log::warning("Table $tableName not found for parameter $parameter");
                    }
                } catch (\Exception $e) {
                    Log::warning("Error accessing table $tableName for parameter $parameter: " . $e->getMessage());
                }
            }
        }

        // Fallback: try common patterns
        $fallbackTables = [
            'state' => 'core_state',
            'country' => 'core_country',
            'district' => 'core_district',
            'city' => 'core_city',
        ];

        foreach ($fallbackTables as $key => $table) {
            if (str_contains($parameter, $key)) {
                try {
                    if (DB::getSchemaBuilder()->hasTable($table)) {
                        $ids = DB::table($table)->pluck('id');
                        if ($ids->isNotEmpty()) {
                            Log::info("Found IDs in fallback table $table for parameter $parameter");
                            return $ids;
                        }
                    }
                } catch (\Exception $e) {
                    // Continue to next
                }
            }
        }

        Log::warning("No source IDs found for parameter: $parameter");
        return collect();
    }

    private function sortApisByDependency($apis)
    {
        $dependencyOrder = [
            'countries',
            'states',
            'districts',
            'city_village',
            'cities',
            'villages',
            'regions',
            'zones',
            'areas',
        ];

        $sorted = [];

        // Add APIs in dependency order
        foreach ($dependencyOrder as $apiName) {
            if (in_array($apiName, $apis)) {
                $sorted[] = $apiName;
            }
        }

        // Add remaining APIs
        foreach ($apis as $api) {
            if (!in_array($api, $sorted)) {
                $sorted[] = $api;
            }
        }

        return $sorted;
    }

    private function createTableIfNotExists($tableName, $columns)
    {
        if (empty($columns)) {
            Log::warning("No columns provided to create table $tableName");
            return;
        }

        // Check if table already exists
        if (DB::getSchemaBuilder()->hasTable($tableName)) {
            // Check if we need to add missing columns
            $existingColumns = DB::getSchemaBuilder()->getColumnListing($tableName);
            $missingColumns = array_diff($columns, $existingColumns);

            if (!empty($missingColumns)) {
                Log::info("Adding missing columns to $tableName: " . implode(', ', $missingColumns));
                foreach ($missingColumns as $column) {
                    try {
                        DB::statement("ALTER TABLE `$tableName` ADD COLUMN `$column` TEXT NULL");
                    } catch (\Exception $e) {
                        Log::warning("Failed to add column $column to $tableName: " . $e->getMessage());
                    }
                }
            }
            return;
        }

        // Create new table
        $createTableQuery = "CREATE TABLE `$tableName` (";

        $columnDefinitions = [];
        foreach ($columns as $column) {
            if ($column === 'id') {
                $columnDefinitions[] = "`id` INT(11) NOT NULL";
            } elseif (str_ends_with($column, '_id')) {
                $columnDefinitions[] = "`$column` INT(11) NULL";
            } elseif (in_array($column, ['created_at', 'updated_at', 'deleted_at'])) {
                $columnDefinitions[] = "`$column` TIMESTAMP NULL";
            } elseif (in_array($column, ['status', 'is_active', 'is_deleted'])) {
                $columnDefinitions[] = "`$column` TINYINT(1) DEFAULT 1";
            } else {
                $columnDefinitions[] = "`$column` TEXT NULL";
            }
        }

        $createTableQuery .= implode(', ', $columnDefinitions);
        $createTableQuery .= ", PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($createTableQuery);
            Log::info("Created table: $tableName with columns: " . implode(', ', $columns));
        } catch (\Exception $e) {
            Log::error("Failed to create table $tableName: " . $e->getMessage());
            throw $e;
        }
    }
}
