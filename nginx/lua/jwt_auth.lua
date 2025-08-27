-- JWT Authentication middleware for Nginx
-- Validates JWT tokens and extracts user information

local jwt = require "resty.jwt"
local cjson = require "cjson"

-- Configuration
local JWT_SECRET = os.getenv("JWT_SECRET") or "your-secret-key"
local JWT_ALGORITHM = "HS256"

-- Helper function to get authorization header
local function get_auth_header()
    local auth_header = ngx.var.http_authorization
    if not auth_header then
        return nil, "Missing Authorization header"
    end
    
    local token = auth_header:match("Bearer%s+(.+)")
    if not token then
        return nil, "Invalid Authorization header format"
    end
    
    return token, nil
end

-- Helper function to set CORS headers
local function set_cors_headers()
    local origin = ngx.var.http_origin
    local allowed_origins = {
        "http://localhost:3000",
        "https://localhost:3000",
        "http://wl-school.local",
        "https://wl-school.local"
    }
    
    -- Check if origin is allowed
    for _, allowed_origin in ipairs(allowed_origins) do
        if origin == allowed_origin then
            ngx.header["Access-Control-Allow-Origin"] = origin
            break
        end
    end
    
    ngx.header["Access-Control-Allow-Methods"] = "GET, POST, PUT, DELETE, OPTIONS, PATCH"
    ngx.header["Access-Control-Allow-Headers"] = "Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-Token"
    ngx.header["Access-Control-Allow-Credentials"] = "true"
    ngx.header["Access-Control-Max-Age"] = "86400"
end

-- Helper function to set security headers
local function set_security_headers()
    ngx.header["X-Frame-Options"] = "DENY"
    ngx.header["X-Content-Type-Options"] = "nosniff"
    ngx.header["X-XSS-Protection"] = "1; mode=block"
    ngx.header["Referrer-Policy"] = "strict-origin-when-cross-origin"
    ngx.header["Content-Security-Policy"] = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' ws: wss:;"
end

-- Main authentication function
local function authenticate()
    -- Set CORS and security headers
    set_cors_headers()
    set_security_headers()
    
    -- Handle preflight requests
    if ngx.var.request_method == "OPTIONS" then
        ngx.status = 200
        ngx.say("")
        ngx.exit(200)
    end
    
    -- Get token from header
    local token, err = get_auth_header()
    if not token then
        ngx.log(ngx.ERR, "JWT Auth Error: ", err)
        ngx.status = 401
        ngx.header.content_type = "application/json"
        ngx.say(cjson.encode({
            error = "Unauthorized",
            message = err
        }))
        ngx.exit(401)
    end
    
    -- Verify JWT token
    local jwt_obj = jwt:verify(JWT_SECRET, token, {
        alg = JWT_ALGORITHM
    })
    
    if not jwt_obj.valid then
        ngx.log(ngx.ERR, "JWT Verification failed: ", jwt_obj.reason)
        ngx.status = 401
        ngx.header.content_type = "application/json"
        ngx.say(cjson.encode({
            error = "Unauthorized",
            message = "Invalid or expired token"
        }))
        ngx.exit(401)
    end
    
    -- Extract user information from JWT payload
    local payload = jwt_obj.payload
    if not payload then
        ngx.log(ngx.ERR, "JWT payload is empty")
        ngx.status = 401
        ngx.header.content_type = "application/json"
        ngx.say(cjson.encode({
            error = "Unauthorized",
            message = "Invalid token payload"
        }))
        ngx.exit(401)
    end
    
    -- Set variables for upstream services
    ngx.var.jwt_user_id = payload.sub or payload.user_id or ""
    ngx.var.jwt_school_id = payload.school_id or ""
    ngx.var.jwt_roles = cjson.encode(payload.roles or {})
    ngx.var.jwt_permissions = cjson.encode(payload.permissions or {})
    
    -- Set headers for upstream services
    ngx.req.set_header("X-User-ID", ngx.var.jwt_user_id)
    ngx.req.set_header("X-School-ID", ngx.var.jwt_school_id)
    ngx.req.set_header("X-User-Roles", ngx.var.jwt_roles)
    ngx.req.set_header("X-User-Permissions", ngx.var.jwt_permissions)
    
    -- Generate request ID for tracing
    local request_id = ngx.var.request_id or ngx.var.connection .. "-" .. ngx.var.connection_requests
    ngx.var.request_id = request_id
    ngx.req.set_header("X-Request-ID", request_id)
    
    ngx.log(ngx.INFO, "JWT Auth successful for user: ", ngx.var.jwt_user_id, " school: ", ngx.var.jwt_school_id)
end

-- Public endpoints that don't require authentication
local function is_public_endpoint()
    local uri = ngx.var.uri
    local public_endpoints = {
        "/api/v1/auth/login",
        "/api/v1/auth/register",
        "/api/v1/auth/forgot-password",
        "/api/v1/auth/reset-password",
        "/health",
        "/metrics"
    }
    
    for _, endpoint in ipairs(public_endpoints) do
        if uri == endpoint or uri:match("^" .. endpoint .. "/") then
            return true
        end
    end
    
    -- Allow static assets
    if uri:match("%.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$") then
        return true
    end
    
    return false
end

-- Main execution
local function main()
    -- Always set CORS and security headers
    set_cors_headers()
    set_security_headers()
    
    -- Handle preflight requests
    if ngx.var.request_method == "OPTIONS" then
        ngx.status = 200
        ngx.say("")
        ngx.exit(200)
    end
    
    -- Check if endpoint requires authentication
    if is_public_endpoint() then
        -- Generate request ID for public endpoints too
        local request_id = ngx.var.request_id or ngx.var.connection .. "-" .. ngx.var.connection_requests
        ngx.var.request_id = request_id
        ngx.req.set_header("X-Request-ID", request_id)
        return
    end
    
    -- Authenticate for protected endpoints
    authenticate()
end

-- Export functions
local _M = {}
_M.main = main
_M.authenticate = authenticate
_M.is_public_endpoint = is_public_endpoint

return _M