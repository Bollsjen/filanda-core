<?php
namespace Core\cors;

final class Cors
{
    private CorsOptions $options;

    public function __construct(CorsOptions $options)
    {
        $this->options = $options;
        $this->handle();
    }

    private function handle(): void
    {
        // Debug logging
        error_log("=== CORS Handler Start ===");
        error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NONE'));
        error_log("HTTP_ORIGIN: " . ($_SERVER['HTTP_ORIGIN'] ?? 'NONE'));
        error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NONE'));
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $isPreflight = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS';

        // Decide which origin to allow, if any
        $allowedOriginHeader = $this->computeAllowedOrigin($origin);
        
        error_log("Allowed Origin Header: " . ($allowedOriginHeader ?? 'NULL'));
        error_log("Is Preflight: " . ($isPreflight ? 'YES' : 'NO'));

        if ($allowedOriginHeader !== null) {
            header("Access-Control-Allow-Origin: {$allowedOriginHeader}");
            header("Vary: Origin");
            error_log("Set ACAO header to: {$allowedOriginHeader}");
        } else {
            error_log("WARNING: Not setting ACAO header!");
        }

        // Allow credentials?
        if ($this->options->allowCredentials && $allowedOriginHeader !== null) {
            header("Access-Control-Allow-Credentials: true");
            error_log("Set credentials: true");
        }

        // Methods
        $methods = $this->normalizeList($this->options->allowedMethods);
        if (!in_array('OPTIONS', $methods, true)) {
            $methods[] = 'OPTIONS';
        }
        $methodsHeader = implode(', ', $methods);
        header('Access-Control-Allow-Methods: ' . $methodsHeader);
        error_log("Allowed methods: " . $methodsHeader);

        // Headers
        $reqHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';
        error_log("Requested headers: " . ($reqHeaders ?: 'NONE'));
        
        if ($isPreflight && $reqHeaders !== '') {
            header('Access-Control-Allow-Headers: ' . $reqHeaders);
            error_log("Echoing back requested headers: " . $reqHeaders);
        } else {
            $allowedHeaders = $this->computeAllowedHeaders($reqHeaders);
            if ($allowedHeaders !== null) {
                header('Access-Control-Allow-Headers: ' . $allowedHeaders);
                error_log("Set allowed headers: " . $allowedHeaders);
            }
        }

        // Cache preflight
        if ($this->options->maxAge !== null) {
            header('Access-Control-Max-Age: ' . (int)$this->options->maxAge);
        }

        // Handle preflight
        if ($isPreflight) {
            error_log("Handling preflight request");
            if ($origin !== null && $allowedOriginHeader === null) {
                error_log("REJECTING preflight - origin not allowed");
                http_response_code(403);
                echo json_encode(['error' => 'Origin not allowed']);
            } else {
                error_log("ACCEPTING preflight with 204");
                http_response_code(204);
            }
            error_log("=== CORS Handler End (Preflight Exit) ===");
            exit;
        }
        
        error_log("=== CORS Handler End (Regular Request) ===");
    }

    private function computeAllowedOrigin(?string $origin): ?string
    {
        $allowAll = in_array('*', $this->options->allowedOrigins, true);
        
        error_log("computeAllowedOrigin - origin: " . ($origin ?? 'NULL'));
        error_log("computeAllowedOrigin - allowAll: " . ($allowAll ? 'YES' : 'NO'));
        error_log("computeAllowedOrigin - configured origins: " . implode(', ', $this->options->allowedOrigins));

        if ($origin === null) {
            // Same-origin or non-CORS request: return null (don't emit ACAO)
            error_log("computeAllowedOrigin - origin is null, returning null");
            return null;
        }

        if ($allowAll) {
            // With credentials: must reflect the exact origin, not '*'
            if ($this->options->allowCredentials) {
                error_log("computeAllowedOrigin - allowAll with credentials, returning origin: " . $origin);
                return $origin;
            }
            // Without credentials: '*' is fine
            error_log("computeAllowedOrigin - allowAll without credentials, returning '*'");
            return '*';
        }

        // Explicit allow-list
        if (in_array($origin, $this->options->allowedOrigins, true)) {
            error_log("computeAllowedOrigin - origin found in allow list, returning: " . $origin);
            return $origin;
        }

        // Not allowed: do NOT fall back to the first allowed origin
        error_log("computeAllowedOrigin - origin NOT in allow list, returning null");
        return null;
    }

    private function computeAllowedHeaders(string $requested): ?string
    {
        // If user configured '*' and it's not a preflight with requested headers,
        // allow a safe default or the configured list.
        if (in_array('*', $this->options->allowedHeaders, true)) {
            return 'Content-Type, Authorization';
        }
        return implode(', ', $this->normalizeList($this->options->allowedHeaders));
    }

    /** @param array<int,string> $list */
    private function normalizeList(array $list): array
    {
        // Upper-case tokens and trim spaces
        return array_values(array_unique(array_map(
            fn ($v) => strtoupper(trim($v)),
            $list
        )));
    }
}