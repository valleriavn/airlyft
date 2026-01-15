<?php
session_start();

class BookingHelper
{
    private $conn;
    private $user_id;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    /**
     * Get user's first name from existing database
     */
    public function getUserFirstName()
    {
        if (!$this->user_id || !$this->conn) return null;

        try {
            $stmt = $this->conn->prepare("SELECT first_name FROM users WHERE user_id = ? LIMIT 1");
            if (!$stmt) return null;

            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $row = $result->fetch_assoc()) {
                return $row['first_name'] ?? null;
            }
        } catch (Exception $e) {
            error_log("getUserFirstName error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get user's full name (first + last)
     */
    public function getUserFullName()
    {
        if (!$this->user_id || !$this->conn) return null;

        try {
            $stmt = $this->conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1");
            if (!$stmt) return null;

            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $row = $result->fetch_assoc()) {
                $first = $row['first_name'] ?? '';
                $last = $row['last_name'] ?? '';
                return trim($first . ' ' . $last) ?: null;
            }
        } catch (Exception $e) {
            error_log("getUserFullName error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get user's previous bookings from existing database
     * Queries existing booking tables to show user's travel history
     */
    public function getUserBookings($limit = 5)
    {
        if (!$this->user_id || !$this->conn) return [];

        try {
            // Query existing bookings table - adjust column names to match your schema
            $stmt = $this->conn->prepare("
                SELECT 
                    b.booking_id,
                    b.booking_date,
                    d.place_name,
                    a.aircraft_type,
                    b.status
                FROM bookings b
                LEFT JOIN destinations d ON b.destination_id = d.destination_id
                LEFT JOIN aircraft a ON b.aircraft_id = a.aircraft_id
                WHERE b.user_id = ?
                ORDER BY b.booking_date DESC
                LIMIT ?
            ");

            if (!$stmt) return [];

            $stmt->bind_param("ii", $this->user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $bookings = [];
            while ($row = $result->fetch_assoc()) {
                $bookings[] = [
                    'booking_id' => $row['booking_id'] ?? null,
                    'place_name' => $row['place_name'] ?? 'Unknown',
                    'aircraft_type' => $row['aircraft_type'] ?? 'Unknown',
                    'booking_date' => $row['booking_date'] ?? null,
                    'status' => $row['status'] ?? 'pending'
                ];
            }

            return $bookings;
        } catch (Exception $e) {
            error_log("getUserBookings error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all available destinations from existing database
     */
    public function getDestinations()
    {
        if (!$this->conn) return [];

        try {
            $stmt = $this->conn->prepare("
                SELECT destination_id, place_name, description 
                FROM destinations 
                WHERE status = 'active'
                LIMIT 12
            ");

            if (!$stmt) return [];

            $stmt->execute();
            $result = $stmt->get_result();

            $destinations = [];
            while ($row = $result->fetch_assoc()) {
                $destinations[] = [
                    'id' => $row['destination_id'],
                    'name' => $row['place_name'],
                    'description' => $row['description'] ?? ''
                ];
            }

            return $destinations;
        } catch (Exception $e) {
            error_log("getDestinations error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all available aircraft from existing database
     */
    public function getAircraft()
    {
        if (!$this->conn) return [];

        try {
            $stmt = $this->conn->prepare("
                SELECT aircraft_id, aircraft_type, capacity, price_per_hour 
                FROM aircraft 
                WHERE status = 'active'
            ");

            if (!$stmt) return [];

            $stmt->execute();
            $result = $stmt->get_result();

            $aircraft = [];
            while ($row = $result->fetch_assoc()) {
                $aircraft[] = [
                    'id' => $row['aircraft_id'],
                    'type' => $row['aircraft_type'],
                    'capacity' => $row['capacity'] ?? 0,
                    'price' => $row['price_per_hour'] ?? 0
                ];
            }

            return $aircraft;
        } catch (Exception $e) {
            error_log("getAircraft error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has admin privileges
     */
    public function isAdmin()
    {
        if (!$this->user_id || !$this->conn) return false;

        try {
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
            if (!$stmt) return false;

            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $row = $result->fetch_assoc()) {
                return strtolower($row['role'] ?? '') === 'admin';
            }
        } catch (Exception $e) {
            error_log("isAdmin error: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Get user's booking preferences based on history
     */
    public function getUserPreferences()
    {
        $bookings = $this->getUserBookings(10);
        if (empty($bookings)) return null;

        $preferences = [
            'favorite_destinations' => [],
            'favorite_aircraft' => [],
            'booking_frequency' => count($bookings)
        ];

        foreach ($bookings as $booking) {
            if ($booking['place_name'] && !in_array($booking['place_name'], $preferences['favorite_destinations'])) {
                $preferences['favorite_destinations'][] = $booking['place_name'];
            }
            if ($booking['aircraft_type'] && !in_array($booking['aircraft_type'], $preferences['favorite_aircraft'])) {
                $preferences['favorite_aircraft'][] = $booking['aircraft_type'];
            }
        }

        return $preferences;
    }

    /**
     * Get user ID
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get database connection
     */
    public function getConnection()
    {
        return $this->conn;
    }
}
