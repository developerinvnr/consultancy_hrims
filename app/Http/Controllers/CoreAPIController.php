<?php

namespace App\Http\Controllers;

use App\Models\Masters\Employee;
use App\Models\Masters\EmployeeReporting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $apiEndPoints = $request->input('api_end_points');
        $CoreAPI = DB::table('core_api_setup')->first();
        $apiKey = $CoreAPI->api_key;
        $baseUrl = $CoreAPI->base_url;
        $prefix = 'core_'; // Define the prefix

        foreach ($apiEndPoints as $api) {
            $apiData = DB::table('core_apis')->where('api_end_point', $api)->first(['parameters', 'table_name']);
            $parameter = $apiData->parameters ?? null;
            $tableName = $prefix . $apiData->table_name;
            if ($parameter) {


                // Fetch all IDs from the corresponding table
                $ids = DB::table($tableName)->pluck('id');

                if ($ids->isEmpty()) {
                    return response()->json(['status' => 400, 'msg' => "No records found in table $tableName"]);
                }

                foreach ($ids as $id) {
                    $response = Http::withHeaders([
                        'api-key' => $apiKey,
                        'Accept' => 'application/json',
                    ])->get("$baseUrl/api/$api", [$parameter => $id]);

                    $this->processApiResponse($tableName, $response, $api, $parameter, $id);
                }
            } else {
                // Simple API call without parameters
                $response = Http::withHeaders([
                    'api-key' => $apiKey,
                    'Accept' => 'application/json',
                ])->get("$baseUrl/api/$api");

                $this->processApiResponse($tableName, $response, $api);
            }
        }
    }

    private function processApiResponse($tableName, $response, $api, $parameter = null, $id = null)
    {
        if ($response->failed()) {
            $errorMsg = $parameter && $id ? "$parameter=$id" : "No parameter";
            Log::error("API sync failed for $api with $errorMsg: HTTP Status {$response->status()}. Response: {$response->body()}");
            return response()->json(['status' => 400, 'msg' => 'Failed to synchronize APIs.']);
        }

        $data = $response->json();

        if (!isset($data['list']) || !is_array($data['list'])) {
            $errorMsg = $parameter && $id ? "$parameter=$id" : "No parameter";
            Log::error("Invalid API response structure for $api with $errorMsg. Response: " . json_encode($data));
            return response()->json(['status' => 400, 'msg' => 'Unexpected API response format.']);
        }

        $columns = array_keys($data['list'][0]);


        // Dynamically create a table if it doesn't exist
        DB::statement($this->generateCreateTableQuery($tableName, $columns));

        foreach ($data['list'] as $item) {
            DB::table($tableName)->updateOrInsert(
                ['id' => $item['id']],
                $item
            );
        }

        return response()->json(['status' => 200, 'msg' => "APIs synchronized successfully" . ($parameter && $id ? " for $parameter=$id" : "")]);
    }

    private function generateCreateTableQuery($tableName, $columns)
    {
        $createTableQuery = "CREATE TABLE IF NOT EXISTS `$tableName` (";

        foreach ($columns as $column) {
            $createTableQuery .= "`$column` VARCHAR(255),"; // Adjust data types as needed
        }

        $createTableQuery .= "PRIMARY KEY (`id`));";

        return $createTableQuery;
    }
}
