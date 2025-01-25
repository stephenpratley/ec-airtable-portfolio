<?php
// Check if file is included
if (!defined('INCLUDED_PATH')) {
    define('INCLUDED_PATH', dirname(__FILE__));
}

class AirtableQuery {
    private $baseId;
    private $apiKey;
    private $debug;
    
    public function __construct(array $config) {
        $this->baseId = $config['baseId'];
        $this->apiKey = $config['apiKey'];
        $this->debug = $config['debug'] ?? false;
    }
    
    public function getRecords($tableName, $maxRecords = 100) {
        $url = "https://api.airtable.com/v0/{$this->baseId}/" . urlencode($tableName);
        
        $params = [
            'maxRecords' => $maxRecords,
            'view' => 'Grid view'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($statusCode !== 200) {
            throw new Exception("Failed to get records: " . $response);
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }
    
    public function getRecord($tableName, $recordId) {
        if (is_array($recordId)) {
            $recordId = $recordId[0];
        }
        
        $url = "https://api.airtable.com/v0/{$this->baseId}/" . urlencode($tableName) . "/" . $recordId;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($statusCode !== 200) {
            throw new Exception("Failed to get record: " . $response);
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }
}

function getPostsData() {
    $config = require dirname(__FILE__) . '/config.php';
    
    try {
        $airtable = new AirtableQuery($config);
        $posts = $airtable->getRecords('Posts');

        
        $results = [];
        foreach ($posts['records'] as $post) {
            $postData = $post['fields'];
            
            // Get client name
            if (isset($postData['Client']) && !empty($postData['Client'])) {
                $clientRecord = $airtable->getRecord('Clients', $postData['Client']);
                if (isset($clientRecord['fields']['Name'])) {
                    $postData['ClientName'] = $clientRecord['fields']['Name'];
                }
            }
            
            // Get platform name
            if (isset($postData['Platform']) && !empty($postData['Platform'])) {
                $platformRecord = $airtable->getRecord('Platforms', $postData['Platform']);
                if (isset($platformRecord['fields']['Name'])) {
                    $postData['PlatformName'] = $platformRecord['fields']['Name'];
                }
            }
            
                        $results[] = $postData;
        }
        
        
        return $results;
        
        
    } catch (Exception $e) {
        error_log("Error fetching posts: " . $e->getMessage());
        return [];
    }
}