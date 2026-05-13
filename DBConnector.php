<?php

class DBConnector {
    private $host = "127.0.0.1";
    private $username = "root";
    private $password = "";
    private $database = "student_org";
    private $port = 3306;

    public function connect() {
        $conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );

        if ($conn->connect_error) {
            die("DB Connection failed: " . $conn->connect_error);
        }

        return $conn;
    }
}