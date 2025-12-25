# Uptime Monitor Test Endpoints

These endpoints **REQUIRE** advanced options to work. They will **FAIL** if the required options are not configured, and **SUCCEED** only when all required options are correctly set. This allows you to verify that your monitor's advanced options are working correctly before moving to production.

**Base URL:** `http://127.0.0.1:8000/test`

## ⚠️ Important: These Endpoints REQUIRE Advanced Options

These endpoints are designed to **fail** without the correct advanced options, so you can verify your monitor configuration is working properly.

---

## Available Test Endpoints

### 1. Index - List All Endpoints
**GET** `/test/`

Returns a list of all available test endpoints with descriptions.

---

### 2. Basic Test (No Advanced Options Required)
**GET** `/test/basic`

Simple test endpoint that returns 200 OK. Use this to verify basic connectivity without any advanced options.

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/basic`
- Expected Status Code: 200
- No advanced options needed

**Expected Result:** ✅ Monitor will pass

---

### 3. Basic Authentication REQUIRED ⚠️
**GET** `/test/basic-auth`

**REQUIRES Basic Authentication** - This endpoint will **FAIL (401)** without Basic Auth, and **SUCCEED (200)** with correct Basic Auth.

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/basic-auth`
- Expected Status Code: 200
- **Basic Auth Username:** `testuser`
- **Basic Auth Password:** `testpass`

**Test Scenarios:**
- ✅ **With correct Basic Auth:** Monitor will pass (200 OK)
- ❌ **Without Basic Auth:** Monitor will fail (401 Unauthorized)
- ❌ **With wrong credentials:** Monitor will fail (401 Unauthorized)

**Response (Success):**
```json
{
  "status": "success",
  "message": "Basic Authentication successful - Monitor will pass",
  "authenticated_user": "testuser",
  "timestamp": "2025-12-20T12:00:00+00:00",
  "method": "GET"
}
```

**Response (Failure):**
```json
{
  "status": "error",
  "message": "Basic Authentication REQUIRED",
  "error": "This endpoint requires Basic Authentication. Configure Basic Auth Username and Password in your monitor settings."
}
```

---

### 4. Custom Headers REQUIRED ⚠️
**GET** `/test/custom-headers`

**REQUIRES Custom Header** - This endpoint will **FAIL (403)** without the required header, and **SUCCEED (200)** with correct header.

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/custom-headers`
- Expected Status Code: 200
- **Custom Request Headers:**
  ```
  X-API-Key: test-key-123
  ```

**Test Scenarios:**
- ✅ **With correct header:** Monitor will pass (200 OK)
- ❌ **Without header:** Monitor will fail (403 Forbidden)
- ❌ **With wrong header value:** Monitor will fail (403 Forbidden)

**Response (Success):**
```json
{
  "status": "success",
  "message": "Custom headers validated - Monitor will pass",
  "custom_headers": {
    "X-API-Key": "test-key-123"
  },
  "validated_header": "X-API-Key"
}
```

**Response (Failure):**
```json
{
  "status": "error",
  "message": "Custom Header REQUIRED",
  "error": "This endpoint requires X-API-Key header. Configure Custom Request Headers in your monitor settings."
}
```

---

### 5. Cache Buster REQUIRED ⚠️
**GET** `/test/cache-buster`

**REQUIRES Cache Buster** - This endpoint will **FAIL (400)** without cache buster, and **SUCCEED (200)** with cache buster enabled.

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/cache-buster`
- Expected Status Code: 200
- **Enable Cache Buster:** ✅ Yes

**Test Scenarios:**
- ✅ **With Cache Buster enabled:** Monitor will pass (200 OK)
- ❌ **Without Cache Buster:** Monitor will fail (400 Bad Request)

**Response (Success):**
```json
{
  "status": "success",
  "message": "Cache buster validated - Monitor will pass",
  "has_cache_buster": true,
  "cache_buster_value": "1234567890"
}
```

**Response (Failure):**
```json
{
  "status": "error",
  "message": "Cache Buster REQUIRED",
  "error": "This endpoint requires a cache buster parameter. Enable Cache Buster in your monitor settings."
}
```

---

### 6. Request Method REQUIRED (POST) ⚠️
**POST** `/test/method-test`

**REQUIRES POST Method** - This endpoint will **FAIL (405)** with GET method, and **SUCCEED (200)** with POST method.

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/method-test`
- Expected Status Code: 200
- **Request Method:** POST

**Test Scenarios:**
- ✅ **With POST method:** Monitor will pass (200 OK)
- ❌ **With GET method (default):** Monitor will fail (405 Method Not Allowed)

**Response (Success):**
```json
{
  "status": "success",
  "message": "Correct HTTP method used - Monitor will pass",
  "received_method": "POST"
}
```

**Response (Failure):**
```json
{
  "status": "error",
  "message": "Wrong HTTP Method",
  "error": "This endpoint requires POST method. Configure Request Method = POST in your monitor settings.",
  "received_method": "GET",
  "required_method": "POST"
}
```

---

### 7. Comprehensive Test (ALL Advanced Options REQUIRED) ⚠️
**POST** `/test/comprehensive`

**REQUIRES ALL Advanced Options** - This endpoint will **FAIL (400)** if ANY option is missing, and **SUCCEED (200)** only when ALL options are correctly configured.

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/comprehensive`
- Expected Status Code: 200
- **Request Method:** POST
- **Basic Auth Username:** `testuser`
- **Basic Auth Password:** `testpass`
- **Custom Request Headers:**
  ```
  X-API-Key: test-key-123
  ```
- **Enable Cache Buster:** ✅ Yes

**Test Scenarios:**
- ✅ **With ALL options correctly configured:** Monitor will pass (200 OK)
- ❌ **Missing ANY option:** Monitor will fail (400 Bad Request) with detailed error messages

**Response (Success):**
```json
{
  "status": "success",
  "message": "All advanced options validated - Monitor will pass",
  "method": "POST",
  "basic_auth": {
    "validated": true,
    "username": "testuser"
  },
  "custom_headers": {
    "validated": true,
    "X-API-Key": "test-key-123"
  },
  "cache_buster": {
    "validated": true
  }
}
```

**Response (Failure - Missing Options):**
```json
{
  "status": "error",
  "message": "Missing or incorrect advanced options",
  "errors": [
    "Request Method must be POST. Configure Request Method = POST in monitor settings.",
    "Basic Authentication REQUIRED. Configure Basic Auth Username and Password in monitor settings.",
    "Custom Header REQUIRED. Add X-API-Key header in Custom Request Headers.",
    "Cache Buster REQUIRED. Enable Cache Buster in monitor settings."
  ]
}
```

---

### 8. Status Code Test
**GET** `/test/status-code?code=XXX`

Returns the specified HTTP status code. Use this to test different expected status codes.

**Usage:**
- `/test/status-code?code=200` - Returns 200 OK
- `/test/status-code?code=404` - Returns 404 Not Found
- `/test/status-code?code=500` - Returns 500 Internal Server Error

**Monitor Configuration:**
- URL: `http://127.0.0.1:8000/test/status-code?code=200`
- Expected Status Code: 200 (or match the code parameter)

---

### 9. Slow Response Test
**GET** `/test/slow-response?delay=X`

Simulates a slow response. Use this to test timeout settings.

**Usage:**
- `/test/slow-response?delay=5` - Waits 5 seconds before responding
- Configure monitor timeout to be less than delay to test timeout handling
- Max delay: 10 seconds

---

### 10. Error Test
**GET** `/test/error`

Returns a 500 error. Use this to test error handling.

**Response:** 500 Internal Server Error

---

### 11. Keyword Test
**GET** `/test/keyword-test?keyword=XXX`

Returns a response containing the specified keyword. Use this to test keyword presence/absence checks.

**Usage:**
- `/test/keyword-test?keyword=success` - Response contains "success"
- Configure monitor: Keyword Must Be Present = "success"
- Or: Keyword Must Be Absent = "error"

---

## Testing Workflow

### Step 1: Test Basic Connectivity
1. Create monitor: `http://127.0.0.1:8000/test/basic`
2. Expected Status Code: 200
3. No advanced options
4. **Expected:** Monitor should pass ✅

### Step 2: Test Basic Authentication
1. Create monitor: `http://127.0.0.1:8000/test/basic-auth`
2. Expected Status Code: 200
3. **Basic Auth Username:** `testuser`
4. **Basic Auth Password:** `testpass`
5. **Expected:** Monitor should pass ✅
6. **Test failure:** Remove Basic Auth → Monitor should fail ❌

### Step 3: Test Custom Headers
1. Create monitor: `http://127.0.0.1:8000/test/custom-headers`
2. Expected Status Code: 200
3. **Custom Headers:** `X-API-Key: test-key-123`
4. **Expected:** Monitor should pass ✅
5. **Test failure:** Remove header → Monitor should fail ❌

### Step 4: Test Cache Buster
1. Create monitor: `http://127.0.0.1:8000/test/cache-buster`
2. Expected Status Code: 200
3. **Enable Cache Buster:** Yes
4. **Expected:** Monitor should pass ✅
5. **Test failure:** Disable Cache Buster → Monitor should fail ❌

### Step 5: Test Request Method
1. Create monitor: `http://127.0.0.1:8000/test/method-test`
2. Expected Status Code: 200
3. **Request Method:** POST
4. **Expected:** Monitor should pass ✅
5. **Test failure:** Use GET method → Monitor should fail ❌

### Step 6: Comprehensive Test (All Options)
1. Create monitor: `http://127.0.0.1:8000/test/comprehensive`
2. Expected Status Code: 200
3. **Request Method:** POST
4. **Basic Auth:** `testuser` / `testpass`
5. **Custom Headers:** `X-API-Key: test-key-123`
6. **Cache Buster:** Enabled
7. **Expected:** Monitor should pass ✅
8. **Test failure:** Remove any option → Monitor should fail ❌

---

## Production Readiness Checklist

Before moving to production, verify:

- [ ] Basic Authentication works correctly
- [ ] Custom Headers are sent properly
- [ ] Cache Buster is functioning
- [ ] Request Method is applied correctly
- [ ] All options work together (comprehensive test)
- [ ] Monitor correctly fails when options are missing
- [ ] Monitor correctly passes when options are correct

---

## Notes

- All test endpoints are publicly accessible (no authentication required)
- These endpoints are designed to **fail** without correct configuration
- Use these endpoints to verify your monitor settings before production
- The comprehensive endpoint tests all features at once
