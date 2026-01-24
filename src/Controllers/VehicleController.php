<?php

namespace Routina\Controllers;

use Routina\Models\Vehicle;
use Routina\Models\VehicleVendor;
use Routina\Models\VehiclePart;
use Routina\Models\VehicleMaintenance;
use Routina\Models\VehicleDocument;
use Routina\Models\VehicleEvent;
use Routina\Models\VehiclePlan;

class VehicleController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $make = trim($_POST['make'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $yearRaw = $_POST['year'] ?? '';
            $plate = trim($_POST['plate'] ?? '');

            $year = is_numeric($yearRaw) ? (int)$yearRaw : null;
            if ($make === '' || $model === '' || $plate === '' || $year === null || $year < 1900 || $year > (int)date('Y') + 2) {
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/index', [
                    'vehicles' => $vehicles,
                    'error' => 'Please provide a valid make, model, year, and license plate.'
                ]);
                return;
            }

            Vehicle::create($_SESSION['user_id'], $make, $model, $year, $plate);
            header('Location: /vehicle');
            exit;
        }

        $vehicles = Vehicle::getAll($_SESSION['user_id']);
        view('vehicle/index', ['vehicles' => $vehicles]);
    }

    public function dashboard() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $vehicles = Vehicle::getAll($_SESSION['user_id']);
        view('vehicle/dashboard', ['vehicles' => $vehicles]);
    }

    public function newVehicle() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $make = trim($_POST['make'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $yearRaw = $_POST['year'] ?? '';
            $plate = trim($_POST['plate'] ?? '');

            $year = is_numeric($yearRaw) ? (int)$yearRaw : null;
            if ($make === '' || $model === '' || $plate === '' || $year === null || $year < 1900 || $year > (int)date('Y') + 2) {
                view('vehicle/new', ['error' => 'Please provide a valid make, model, year, and license plate.']);
                return;
            }

            Vehicle::create($_SESSION['user_id'], $make, $model, $year, $plate);
            header('Location: /vehicle');
            exit;
        }

        view('vehicle/new');
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $id = $_GET['id'] ?? '';
        if (!is_numeric($id)) {
            header('Location: /vehicle');
            exit;
        }

        $vehicle = Vehicle::find($_SESSION['user_id'], (int)$id);
        if (!$vehicle) {
            header('Location: /vehicle');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $make = trim($_POST['make'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $yearRaw = $_POST['year'] ?? '';
            $plate = trim($_POST['plate'] ?? '');
            $status = $_POST['status'] ?? 'active';

            $year = is_numeric($yearRaw) ? (int)$yearRaw : null;
            if ($make === '' || $model === '' || $plate === '' || $year === null || $year < 1900 || $year > (int)date('Y') + 2) {
                view('vehicle/edit', ['vehicle' => $vehicle, 'error' => 'Please provide a valid make, model, year, and license plate.']);
                return;
            }

            Vehicle::update($_SESSION['user_id'], (int)$id, $make, $model, $year, $plate, $status);
            header('Location: /vehicle');
            exit;
        }

        view('vehicle/edit', ['vehicle' => $vehicle]);
    }

    public function vendors() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            if ($name === '') {
                $vendors = VehicleVendor::getAll($_SESSION['user_id']);
                view('vehicle/vendors', ['vendors' => $vendors, 'error' => 'Please provide a vendor name.']);
                return;
            }

            VehicleVendor::create($_SESSION['user_id'], $name, $phone, $email, $notes);
            header('Location: /vehicle/vendors');
            exit;
        }

        $vendors = VehicleVendor::getAll($_SESSION['user_id']);
        view('vehicle/vendors', ['vendors' => $vendors]);
    }

    public function parts() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $partNumber = trim($_POST['part_number'] ?? '');
            $vendorId = $_POST['vendor_id'] ?? null;
            $costRaw = $_POST['cost'] ?? '';

            if ($name === '' || $costRaw === '' || !is_numeric($costRaw)) {
                $parts = VehiclePart::getAll($_SESSION['user_id']);
                $vendors = VehicleVendor::getAll($_SESSION['user_id']);
                view('vehicle/parts', ['parts' => $parts, 'vendors' => $vendors, 'error' => 'Please provide a name and cost.']);
                return;
            }

            $vendorId = is_numeric($vendorId) ? (int)$vendorId : null;

            $ok = VehiclePart::create($_SESSION['user_id'], $name, $partNumber, $vendorId, (float)$costRaw);
            if (!$ok) {
                $parts = VehiclePart::getAll($_SESSION['user_id']);
                $vendors = VehicleVendor::getAll($_SESSION['user_id']);
                view('vehicle/parts', ['parts' => $parts, 'vendors' => $vendors, 'error' => 'Invalid vendor selected.']);
                return;
            }

            header('Location: /vehicle/parts');
            exit;
        }

        $parts = VehiclePart::getAll($_SESSION['user_id']);
        $vendors = VehicleVendor::getAll($_SESSION['user_id']);
        view('vehicle/parts', ['parts' => $parts, 'vendors' => $vendors]);
    }

    public function maintenance() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = $_POST['vehicle_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $status = $_POST['status'] ?? 'open';
            $dueDate = $_POST['due_date'] ?? '';
            $notes = trim($_POST['notes'] ?? '');

            if ($title === '' || !is_numeric($vehicleId)) {
                $items = VehicleMaintenance::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/maintenance', ['items' => $items, 'vehicles' => $vehicles, 'error' => 'Please select a vehicle and title.']);
                return;
            }

            $ok = VehicleMaintenance::create($_SESSION['user_id'], (int)$vehicleId, $title, $status, $dueDate, $notes);
            if (!$ok) {
                $items = VehicleMaintenance::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/maintenance', ['items' => $items, 'vehicles' => $vehicles, 'error' => 'Invalid vehicle selected.']);
                return;
            }

            header('Location: /vehicle/maintenance');
            exit;
        }

        $items = VehicleMaintenance::getAll($_SESSION['user_id']);
        $vehicles = Vehicle::getAll($_SESSION['user_id']);
        view('vehicle/maintenance', ['items' => $items, 'vehicles' => $vehicles]);
    }

    public function documents() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = $_POST['vehicle_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $fileUrl = trim($_POST['file_url'] ?? '');

            if ($title === '' || $fileUrl === '' || !is_numeric($vehicleId)) {
                $docs = VehicleDocument::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/documents', ['documents' => $docs, 'vehicles' => $vehicles, 'error' => 'Please provide a vehicle, title, and file URL.']);
                return;
            }

            $ok = VehicleDocument::create($_SESSION['user_id'], (int)$vehicleId, $title, $fileUrl);
            if (!$ok) {
                $docs = VehicleDocument::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/documents', ['documents' => $docs, 'vehicles' => $vehicles, 'error' => 'Invalid vehicle selected.']);
                return;
            }

            header('Location: /vehicle/documents');
            exit;
        }

        $documents = VehicleDocument::getAll($_SESSION['user_id']);
        $vehicles = Vehicle::getAll($_SESSION['user_id']);
        view('vehicle/documents', ['documents' => $documents, 'vehicles' => $vehicles]);
    }

    public function events() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = $_POST['vehicle_id'] ?? '';
            $type = trim($_POST['event_type'] ?? '');
            $date = $_POST['event_date'] ?? '';
            $notes = trim($_POST['notes'] ?? '');

            if ($type === '' || $date === '' || !is_numeric($vehicleId)) {
                $events = VehicleEvent::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/events', ['events' => $events, 'vehicles' => $vehicles, 'error' => 'Please provide a vehicle, type, and date.']);
                return;
            }

            $ok = VehicleEvent::create($_SESSION['user_id'], (int)$vehicleId, $type, $date, $notes);
            if (!$ok) {
                $events = VehicleEvent::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/events', ['events' => $events, 'vehicles' => $vehicles, 'error' => 'Invalid vehicle selected.']);
                return;
            }

            header('Location: /vehicle/events');
            exit;
        }

        $events = VehicleEvent::getAll($_SESSION['user_id']);
        $vehicles = Vehicle::getAll($_SESSION['user_id']);
        view('vehicle/events', ['events' => $events, 'vehicles' => $vehicles]);
    }

    public function plans() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehicleId = $_POST['vehicle_id'] ?? '';
            $title = trim($_POST['title'] ?? '');
            $status = $_POST['status'] ?? 'planned';
            $notes = trim($_POST['notes'] ?? '');

            if ($title === '' || !is_numeric($vehicleId)) {
                $plans = VehiclePlan::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/plans', ['plans' => $plans, 'vehicles' => $vehicles, 'error' => 'Please provide a vehicle and title.']);
                return;
            }

            $ok = VehiclePlan::create($_SESSION['user_id'], (int)$vehicleId, $title, $status, $notes);
            if (!$ok) {
                $plans = VehiclePlan::getAll($_SESSION['user_id']);
                $vehicles = Vehicle::getAll($_SESSION['user_id']);
                view('vehicle/plans', ['plans' => $plans, 'vehicles' => $vehicles, 'error' => 'Invalid vehicle selected.']);
                return;
            }

            header('Location: /vehicle/plans');
            exit;
        }

        $plans = VehiclePlan::getAll($_SESSION['user_id']);
        $vehicles = Vehicle::getAll($_SESSION['user_id']);
        view('vehicle/plans', ['plans' => $plans, 'vehicles' => $vehicles]);
    }
}
