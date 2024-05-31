<?php
function sanitizeInput($input) {
    // Remove all <script> tags and their content
    $input = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $input);

    // Remove other potentially dangerous HTML tags (e.g., iframe, object, embed)
    $input = preg_replace('#<(iframe|object|embed|applet|form|input|button)(.*?)>(.*?)</\1>#is', '', $input);

    // Strip all tags except a few harmless ones (optional, depending on needs)
    $input = strip_tags($input, '<p><a><b><i><strong><em><ul><ol><li>');

    // Convert special characters to HTML entities to prevent HTML injection
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

    // Optionally, remove any remaining potential threats (e.g., event handlers)
    $input = preg_replace('#on[a-z]+\s*=\s*["\'][^"\']*["\']#i', '', $input);

    // Remove any potential JavaScript in CSS (e.g., expression(), url(javascript:...))
    $input = preg_replace('#style\s*=\s*["\'][^"\']*expression\([^"\']*\)["\']#i', '', $input);
    $input = preg_replace('#style\s*=\s*["\'][^"\']*url\(["\']?javascript:[^"\']*["\']?\)["\']#i', '', $input);

    // Remove dangerous protocols (e.g., javascript:, data:, vbscript:)
    $input = preg_replace('#(javascript|data|vbscript):#i', '', $input);

    return $input;
}
?>
