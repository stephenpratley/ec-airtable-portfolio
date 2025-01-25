<?php

// Include the config file
$config = require '../config.php';

class AirtableCRUD {
    private $apiKey;
    private $baseId;
    private $tableName;
    private $baseUrl = 'https://api.airtable.com/v0/';

    public function __construct($apiKey, $baseId, $tableName) {
        $this->apiKey = $apiKey;
        $this->baseId = $baseId;
        $this->tableName = $tableName;
    }

    private function sendRequest($method, $url, $data = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function createRecord($fields) {
        $url = $this->baseUrl . $this->baseId . '/' . $this->tableName;
        $data = ['fields' => $fields];
        return $this->sendRequest('POST', $url, $data);
    }

    public function readRecords() {
        $url = $this->baseUrl . $this->baseId . '/' . $this->tableName;
        return $this->sendRequest('GET', $url);
    }

    public function updateRecord($recordId, $fields) {
        $url = $this->baseUrl . $this->baseId . '/' . $this->tableName . '/' . $recordId;
        $data = ['fields' => $fields];
        return $this->sendRequest('PATCH', $url, $data);
    }

    public function getTableSchema() {
        $url = $this->baseUrl . 'meta/bases/' . $this->baseId . '/tables';
        $response = $this->sendRequest('GET', $url);
        foreach ($response['tables'] as $table) {
            if ($table['name'] === $this->tableName) {
                return $table['fields'];
            }
        }
        return [];
    }
}

// Use configuration values from config.php
$airtable = new AirtableCRUD($config['apiKey'], $config['baseId'], $config['tableName']);

// Fetch table schema
$schema = $airtable->getTableSchema();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // Add new record
        $fields = [];
        foreach ($schema as $field) {
            $fieldName = $field['name'];
            if (isset($_POST[$fieldName])) {
                $fields[$fieldName] = $_POST[$fieldName];
            }
        }
        $airtable->createRecord($fields);
    } elseif (isset($_POST['edit'])) {
        // Update existing record
        $recordId = $_POST['record_id'];
        $fields = [];
        foreach ($schema as $field) {
            $fieldName = $field['name'];
            if (isset($_POST['edit_' . $fieldName])) {
                $fields[$fieldName] = $_POST['edit_' . $fieldName];
            }
        }
        $airtable->updateRecord($recordId, $fields);
    }
    // Redirect to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch records from Airtable
$records = $airtable->readRecords();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airtable CRUD</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        input[type="text"], input[type="email"], input[type="number"], input[type="date"] {
            width: 100%;
            padding: 5px;
        }
    </style>
</head>
<body>
    <h1>Airtable Table: <?php echo $config['tableName']; ?></h1>

    <!-- Form to Add New Record -->
    <h2>Add New Record</h2>
    <form method="POST">
        <?php foreach ($schema as $field): ?>
            <label for="<?php echo $field['name']; ?>"><?php echo $field['name']; ?>:</label>
            <?php echo getInputField($field); ?><br>
        <?php endforeach; ?>
        <button type="submit" name="add">Add Record</button>
    </form>

    <!-- Display Existing Records -->
    <h2>Existing Records</h2>
    <table>
        <thead>
            <tr>
                <?php foreach ($schema as $field): ?>
                    <th><?php echo $field['name']; ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($records['records'])): ?>
                <?php foreach ($records['records'] as $record): ?>
                    <tr>
                        <?php foreach ($schema as $field): ?>
                            <td><?php echo htmlspecialchars($record['fields'][$field['name']] ?? ''); ?></td>
                        <?php endforeach; ?>
                        <td>
                            <!-- Form to Edit Record -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                <?php foreach ($schema as $field): ?>
                                    <?php echo getInputField($field, $record['fields'][$field['name']] ?? ''); ?>
                                <?php endforeach; ?>
                                <button type="submit" name="edit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo count($schema) + 1; ?>">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
// Helper function to generate input fields based on field type
function getInputField($field, $value = '') {
    $fieldName = $field['name'];
    $fieldType = $field['type'];
    $inputName = isset($value) ? 'edit_' . $fieldName : $fieldName;
    $inputValue = htmlspecialchars($value);

    switch ($fieldType) {
        case 'email':
            return "<input type='email' name='$inputName' value='$inputValue' required>";
        case 'number':
            return "<input type='number' name='$inputName' value='$inputValue' required>";
        case 'date':
            return "<input type='date' name='$inputName' value='$inputValue' required>";
        case 'checkbox':
            $checked = $inputValue ? 'checked' : '';
            return "<input type='checkbox' name='$inputName' $checked>";
        default:
            return "<input type='text' name='$inputName' value='$inputValue' required>";
    }
}
?>