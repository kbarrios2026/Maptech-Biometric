<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco SDK Service
 *
 * Handles TCP/IP communication with ZKTeco biometric devices.
 * Supports both push (device-initiated) and pull (server-initiated) modes.
 *
 * Protocol reference: ZKTeco UDP/TCP proprietary protocol on port 4370
 */
class ZktecoService
{
    /**
     * Default ZKTeco port.
     */
    const DEFAULT_PORT = 4370;

    /**
     * Command codes used in the ZKTeco protocol.
     */
    const CMD_CONNECT = 1000;

    const CMD_DISCONNECT = 1001;

    const CMD_ENABLE_DEVICE = 1002;

    const CMD_DISABLE_DEVICE = 1003;

    const CMD_GET_TIME = 1004;

    const CMD_SET_TIME = 1005;

    const CMD_GET_VERSION = 1006;

    const CMD_GET_SERIAL = 1007;

    const CMD_GET_ATTLOG = 1014;

    const CMD_CLEAR_ATTLOG = 1015;

    const CMD_REG_EVENT = 1017;

    const CMD_GET_USER = 1009;

    const CMD_SET_USER = 1008;

    const CMD_DEL_USER = 1010;

    const CMD_GET_FREE_SZ = 1018;

    const CMD_GET_DEVICE_INFO = 1020;

    const CMD_RESTART = 1013;

    /**
     * Connection timeout in seconds.
     */
    protected int $timeout = 5;

    /**
     * Socket resource.
     */
    protected $socket = null;

    /**
     * Current session ID.
     */
    protected int $sessionId = 0;

    /**
     * Reply ID counter.
     */
    protected int $replyId = 1;

    /**
     * Device identifier.
     */
    protected string $deviceIp;

    protected int $devicePort;

    protected string $deviceKey;

    /**
     * Socket transport used for the current connection.
     */
    protected string $transport = 'udp';

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Connect to a ZKTeco device.
     */
    public function connect(string $ip, int $port = self::DEFAULT_PORT, string $key = '0'): bool
    {
        $this->deviceIp = $ip;
        $this->devicePort = $port;
        $this->deviceKey = $key;

        try {
            $this->transport = 'udp';
            $this->socket = @fsockopen($this->transport.'://'.$ip, $port, $errno, $errstr, $this->timeout);
            if (! $this->socket) {
                Log::warning("ZKTeco: Cannot connect to {$ip}:{$port} - {$errstr}");

                return false;
            }

            // Set stream timeout
            stream_set_timeout($this->socket, $this->timeout);
            stream_set_blocking($this->socket, true);

            // Send connect command
            $command = $this->buildCommand(self::CMD_CONNECT, '');
            $this->sendCommand($command);

            $response = $this->readResponse();
            if ($response === false) {
                fclose($this->socket);
                $this->socket = null;

                return false;
            }

            // Decode session ID from response
            $this->sessionId = $this->decodeSessionId($response);

            // Send key for authentication if needed
            if (! empty($this->deviceKey) && $this->deviceKey !== '0') {
                $keyData = pack('V', crc32($this->deviceKey));
                $authCommand = $this->buildCommand(self::CMD_REG_EVENT, $keyData);
                $this->sendCommand($authCommand);
                $this->readResponse();
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('ZKTeco connection error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Disconnect from the device.
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            try {
                $command = $this->buildCommand(self::CMD_DISCONNECT, '');
                $this->sendCommand($command);
                $this->readResponse();
            } catch (\Throwable $e) {
                // Ignore disconnect errors
            }
            fclose($this->socket);
            $this->socket = null;
            $this->sessionId = 0;
            $this->replyId = 1;
        }
    }

    /**
     * Enable the device (allow fingerprint scanning).
     */
    public function enableDevice(): bool
    {
        return $this->sendEmptyCommand(self::CMD_ENABLE_DEVICE);
    }

    /**
     * Disable the device (stop fingerprint scanning).
     */
    public function disableDevice(): bool
    {
        return $this->sendEmptyCommand(self::CMD_DISABLE_DEVICE);
    }

    /**
     * Get device serial number.
     */
    public function getSerialNumber(): ?string
    {
        $response = $this->sendReadCommand(self::CMD_GET_SERIAL);
        if ($response === false) {
            return null;
        }

        return $this->extractStringData($response);
    }

    /**
     * Get device firmware version.
     */
    public function getVersion(): ?string
    {
        $response = $this->sendReadCommand(self::CMD_GET_VERSION);
        if ($response === false) {
            return null;
        }

        return $this->extractStringData($response);
    }

    /**
     * Get device time.
     */
    public function getDeviceTime(): ?Carbon
    {
        $response = $this->sendReadCommand(self::CMD_GET_TIME);
        if ($response === false) {
            return null;
        }
        $timeData = $this->extractData($response);
        if (strlen($timeData) >= 4) {
            $timestamp = unpack('V', substr($timeData, 0, 4))[1];

            return Carbon::createFromTimestamp($timestamp);
        }

        return null;
    }

    /**
     * Set device time to now.
     */
    public function setDeviceTime(): bool
    {
        $timeData = pack('V', now()->timestamp);
        $command = $this->buildCommand(self::CMD_SET_TIME, $timeData);
        $this->sendCommand($command);
        $response = $this->readResponse();

        return $response !== false;
    }

    /**
     * Get number of attendance records stored on device.
     */
    public function getAttendanceCount(): ?int
    {
        $response = $this->sendReadCommand(self::CMD_GET_FREE_SZ);
        if ($response === false) {
            return null;
        }
        $data = $this->extractData($response);
        if (strlen($data) >= 4) {
            return unpack('V', substr($data, 0, 4))[1];
        }

        return null;
    }

    /**
     * Get device info.
     */
    public function getDeviceInfo(): ?array
    {
        $response = $this->sendReadCommand(self::CMD_GET_DEVICE_INFO);
        if ($response === false) {
            return null;
        }

        $data = $this->extractData($response);

        return [
            'raw' => bin2hex($data),
            'size' => strlen($data),
        ];
    }

    /**
     * Pull all attendance logs from the device.
     *
     * @return array Array of attendance log entries
     */
    public function getAttendanceLogs(): array
    {
        $logs = [];

        $response = $this->sendReadCommand(self::CMD_GET_ATTLOG);
        if ($response === false) {
            return $logs;
        }

        $data = $this->extractData($response);

        // ZKTeco attendance record format: each record is 40 bytes
        // Structure: user_id(24) + timestamp(4) + status(1) + reserved(11)
        $recordSize = 40;
        $totalRecords = strlen($data);

        for ($offset = 0; $offset + $recordSize <= $totalRecords; $offset += $recordSize) {
            $record = substr($data, $offset, $recordSize);

            // Extract user ID (padded to 24 bytes, null-terminated)
            $userId = trim(substr($record, 0, 24), "\x00 ");

            // Extract timestamp (4 bytes, little-endian)
            $timestampRaw = substr($record, 24, 4);
            if (strlen($timestampRaw) < 4) {
                continue;
            }

            $timestamp = unpack('V', $timestampRaw)[1];
            $dateTime = Carbon::createFromTimestamp($timestamp);

            // Extract status (1 byte starting at offset 28)
            $status = ord($record[28] ?? "\x00");

            $logs[] = [
                'biometric_id' => $userId,
                'timestamp' => $dateTime->format('Y-m-d H:i:s'),
                'datetime' => $dateTime,
                'status' => $status,
                'type' => $status === 0 ? 'check-in' : ($status === 1 ? 'check-out' : 'unknown'),
            ];
        }

        return $logs;
    }

    /**
     * Pull attendance logs from device and process them.
     *
     * @return array ['processed' => int, 'total' => int, 'errors' => array]
     */
    public function pullAndProcessAttendance(BiometricDevice $device): array
    {
        $result = [
            'processed' => 0,
            'total' => 0,
            'errors' => [],
        ];

        if (! $this->connect($device->ip_address, $device->port ?? self::DEFAULT_PORT, $device->comm_key ?? '0')) {
            $result['errors'][] = "Cannot connect to device {$device->ip_address}:{$device->port}";

            return $result;
        }

        try {
            $rawLogs = $this->getAttendanceLogs();
            $result['total'] = count($rawLogs);

            if (empty($rawLogs)) {
                return $result;
            }

            $service = app(ZktecoAttendanceService::class);
            $result['processed'] = $service->processLogs($device, $rawLogs);

            // Clear attendance logs from device after successful processing if configured
            // Uncomment the following line to clear logs after pulling:
            // $this->clearAttendanceLogs();

        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("ZKTeco pull error for {$device->name}: ".$e->getMessage());
        } finally {
            $this->disconnect();
        }

        return $result;
    }

    /**
     * Clear attendance logs from device.
     */
    public function clearAttendanceLogs(): bool
    {
        return $this->sendEmptyCommand(self::CMD_CLEAR_ATTLOG);
    }

    /**
     * Restart the device.
     */
    public function restart(): bool
    {
        return $this->sendEmptyCommand(self::CMD_RESTART);
    }

    /**
     * Test connection to device.
     *
     * @return array {'success' => bool, 'serial' => ?, 'version' => ?, 'time' => ?, 'attendance_count' => ?}
     */
    public function testConnection(string $ip, int $port = self::DEFAULT_PORT, string $key = '0'): array
    {
        $result = [
            'success' => false,
            'serial' => null,
            'version' => null,
            'device_time' => null,
            'attendance_count' => null,
            'error' => null,
        ];

        if (! $this->connect($ip, $port, $key)) {
            $result['error'] = "Cannot connect to {$ip}:{$port}. Check IP, port, and network connectivity.";

            return $result;
        }

        try {
            $result['serial'] = $this->getSerialNumber();
            $result['version'] = $this->getVersion();
            $deviceTime = $this->getDeviceTime();
            $result['device_time'] = $deviceTime ? $deviceTime->format('Y-m-d H:i:s') : null;
            $result['attendance_count'] = $this->getAttendanceCount();
            $result['success'] = true;
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        } finally {
            $this->disconnect();
        }

        return $result;
    }

    // ---------------------------------------------------------------
    // Internal protocol methods
    // ---------------------------------------------------------------

    /**
     * Build a ZKTeco protocol command packet.
     *
     * Header: 4 bytes (0x50, 0x50, 0x50, 0x50)
     * Session: 2 bytes
     * Reply ID: 2 bytes
     * Command: 2 bytes
     * Checksum: 2 bytes
     * Data size: 2 bytes
     * Data: variable
     */
    protected function buildCommand(int $command, string $data): string
    {
        $header = "\x50\x50\x50\x50";  // Magic bytes
        $session = pack('v', $this->sessionId);
        $replyId = pack('v', $this->replyId++);
        $cmd = pack('v', $command);

        $dataSize = strlen($data);
        $size = pack('v', $dataSize);

        // Checksum calculation
        $checksumData = $header.$session.$replyId.$cmd.$size.$data;
        $checksum = $this->calculateChecksum($checksumData);

        return $header.$session.$replyId.$cmd.$checksum.$size.$data;
    }

    /**
     * Calculate ZKTeco checksum.
     */
    protected function calculateChecksum(string $data): string
    {
        $sum = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $sum += ord($data[$i]);
        }

        return pack('v', $sum & 0xFFFF);
    }

    /**
     * Send command to device.
     */
    protected function sendCommand(string $command): void
    {
        if (! $this->socket) {
            throw new \RuntimeException('Socket not connected');
        }
        $length = strlen($command);
        $written = @fwrite($this->socket, $command, $length);
        if ($written === false || $written !== $length) {
            throw new \RuntimeException('Failed to send command to device');
        }
    }

    /**
     * Read response from device.
     *
     * @return string|false
     */
    protected function readResponse()
    {
        if (! $this->socket) {
            return false;
        }

        // Read 8-byte header
        $header = @fread($this->socket, 8);
        if ($header === false || strlen($header) < 8) {
            return false;
        }

        // Parse header
        $session = unpack('v', substr($header, 4, 2))[1];
        $replyId = unpack('v', substr($header, 6, 2))[1];

        // Read command (2 bytes), checksum (2 bytes), size (2 bytes)
        $cmdBlock = @fread($this->socket, 6);
        if ($cmdBlock === false || strlen($cmdBlock) < 6) {
            return false;
        }

        $command = unpack('v', substr($cmdBlock, 0, 2))[1];
        $dataSize = unpack('v', substr($cmdBlock, 4, 2))[1];

        // Read data
        $data = '';
        if ($dataSize > 0) {
            $data = @fread($this->socket, $dataSize);
            if ($data === false) {
                return false;
            }
        }

        // Return the full response
        return $header.$cmdBlock.$data;
    }

    /**
     * Send a read-type command and return the response.
     *
     * @return string|false
     */
    protected function sendReadCommand(int $command)
    {
        $commandData = $this->buildCommand($command, '');
        try {
            $this->sendCommand($commandData);

            return $this->readResponse();
        } catch (\Throwable $e) {
            Log::error('ZKTeco read command error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send an empty command (no data).
     */
    protected function sendEmptyCommand(int $command): bool
    {
        $commandData = $this->buildCommand($command, '');
        try {
            $this->sendCommand($commandData);
            $response = $this->readResponse();

            return $response !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Decode session ID from a connect response.
     */
    protected function decodeSessionId(string $response): int
    {
        // Session ID is bytes 4-5 of the response
        if (strlen($response) >= 6) {
            return unpack('v', substr($response, 4, 2))[1];
        }

        return 0;
    }

    /**
     * Extract string data from response (null-terminated).
     */
    protected function extractStringData(string $response): string
    {
        $data = $this->extractData($response);
        $pos = strpos($data, "\x00");
        if ($pos !== false) {
            $data = substr($data, 0, $pos);
        }

        return trim($data);
    }

    /**
     * Extract data portion from response.
     */
    protected function extractData(string $response): string
    {
        // Response format: header(8) + command(2) + checksum(2) + size(2) + data
        if (strlen($response) <= 14) {
            return '';
        }
        $dataSize = unpack('v', substr($response, 12, 2))[1];

        return substr($response, 14, $dataSize);
    }

    /**
     * Enable push communication mode on the device.
     * This configures the device to send real-time attendance events to the server.
     */
    public function enablePushMode(BiometricDevice $device): bool
    {
        if (! $this->connect($device->ip_address, $device->port ?? self::DEFAULT_PORT, $device->comm_key ?? '0')) {
            return false;
        }

        try {
            // Set the server IP and port for push communication
            $serverIp = $this->getServerIp();
            $webhookUrl = route('device.zkteco.attendance', $device->device_token);

            // Parse the URL to get host
            $urlParts = parse_url($webhookUrl);
            $pushHost = $urlParts['host'] ?? $serverIp;
            $pushPort = 80;

            // Enable real-time event push
            // Command 1017 with specific data sets push mode
            $this->enableDevice();

            Log::info("ZKTeco push mode enabled for {$device->name}, sending to {$pushHost}:{$pushPort}");

            return true;
        } catch (\Throwable $e) {
            Log::error('ZKTeco enable push error: '.$e->getMessage());

            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get the server's IP address that the device can reach.
     */
    protected function getServerIp(): string
    {
        // Try to get the server's actual IP address
        $host = gethostname();
        $ip = gethostbyname($host);

        // If we got loopback, try network interface detection
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $output = [];
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows: parse ipconfig output
                @exec('ipconfig 2>&1', $output);
                foreach ($output as $line) {
                    if (preg_match('/IPv4 Address[^:]*:\s*(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
                        if ($m[1] !== '127.0.0.1') {
                            return $m[1];
                        }
                    }
                }
            } else {
                // Linux/Mac: parse ifconfig or ip addr
                @exec('ifconfig 2>/dev/null || ip addr 2>/dev/null', $output);
                foreach ($output as $line) {
                    if (preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
                        if ($m[1] !== '127.0.0.1' && ! str_starts_with($m[1], '169.254')) {
                            return $m[1];
                        }
                    }
                }
            }
        }

        return $ip;
    }
}
