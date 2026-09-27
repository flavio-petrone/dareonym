<?php
// Copy to https-proxy.php ONLY behind a trusted TLS-terminating hosting proxy.
// It must overwrite X-Forwarded-Proto; the origin must not be publicly reachable.
// Set true after verifying these conditions with your hosting provider.
return false;
