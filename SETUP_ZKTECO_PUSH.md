# ZKTeco Device Configuration - PUSH Mode Setup

## Problem
The ZKTeco device at 192.168.1.186 is not pushing attendance data to the server.

## Solution
You need to configure the device's PUSH URL to point to your server.

---

## Step 1: Access ZKTeco Device Web Interface

1. **Open browser:** Go to http://192.168.1.186:8000
2. **Login:** 
   - Username: `admin`
   - Password: (default password - usually `123456` or blank)
3. **Navigate:** Find "Settings" → "Network" or "Attendance Upload"

---

## Step 2: Configure PUSH URL

In the device settings, look for one of these:
- **Attendance Server URL**
- **ADMS Server Address**
- **Push Server URL**
- **Attendance Upload URL**
- **Post URL**

Set it to:
```
http://192.168.1.50:8000/iclock/cdata
```

Alternative formats it might accept:
```
http://192.168.1.50:8000/api/iclock/cdata
192.168.1.50:8000
192.168.1.50 (with port 8000 separately)
```

---

## Step 3: Configure Interval

Set the attendance push interval to:
- **15 seconds** (for real-time updates)
- Or **1 minute** (default)
- Or **5 minutes** (if network is slow)

---

## Step 4: Test Connection

1. **In device settings:** Look for "Test Connection" or "Send Test" button
2. **Expected Result:** Connection should succeed (status = OK)

If connection fails:
- Check device can reach 192.168.1.50 with `ping 192.168.1.50`
- Verify server is running: `php artisan serve --host=0.0.0.0 --port=8000`
- Check firewall settings

---

## Step 5: Monitor Attendance

Once configured, fingerprint scans should:
1. Be recorded on the device immediately
2. Be sent to server within 15-60 seconds
3. Appear in the web dashboard at http://192.168.1.50:8000/admin/biometric-devices/1

---

## Server Status

Your Laravel server is ready to receive data:
- ✅ Running on http://192.168.1.50:8000
- ✅ Endpoint ready: POST /iclock/cdata
- ✅ Database connected
- ✅ Logging enabled

---

## Troubleshooting

### Check if data is being received:

```bash
# View server logs for incoming data
Get-Content storage/logs/laravel.log -Tail 20

# Expected log entry when data arrives:
# "Device pushed attendance: biometric_id=12, time=07:22:14"
```

### Check device connectivity:

```bash
# From your computer, ping the device
ping 192.168.1.186

# Try to access device web interface
http://192.168.1.186:8000
```

### Reset device push settings:

If you can't find the setting, try:
1. Device settings → Reset to defaults
2. Device settings → Network → DHCP (if static IP)
3. Contact ZKTeco support for your model

---

## Quick Command Reference

```bash
# View recent logs (last 50 lines)
Get-Content storage/logs/laravel.log -Tail 50

# Check for ADMS push entries
Get-Content storage/logs/laravel.log -Tail 100 | Select-String "ADMS|attendance|cdata"

# Start server (if not running)
php artisan serve --host=0.0.0.0 --port=8000
```

---

## Device Model Reference

**Your Device:** ZKTeco BRWU232160143
- **Web Interface Port:** 8000 (HTTP ADMS)
- **Push Protocol:** HTTP POST
- **Expected Push Format:** Tab-separated attendance data
- **Push Interval:** Configurable (default: 15-60 seconds)

---

Once you configure the device, fingerprints will be instantly visible in the dashboard! 🎉
