<?php

/**
 * Flash Messages Handler
 * 
 * Usage:
 * - To set a message for a page: setFlash('skills', 'success', 'Message text here');
 * - To display messages for a page: showFlash('skills');
 */

/* ---------------------------
   Set Flash Message Function
--------------------------- */
function setFlash(string $page, string $type, string $message): void
{
    if (!in_array($type, ['success', 'error', 'info', 'warning'])) {
        $type = 'info';
    }

    $_SESSION['flash_messages'][$page][] = [
        'type' => $type,
        'message' => $message
    ];
}

/* ---------------------------
   Display Flash Messages
--------------------------- */
function showFlash(string $page): void
{
    if (!empty($_SESSION['flash_messages'][$page])) {
        echo '<div class="flash-messages">';
        foreach ($_SESSION['flash_messages'][$page] as $flash) {
            $class = match ($flash['type']) {
                'success' => 'alert-success',
                'error'   => 'alert-error',
                'warning' => 'alert-warning',
                default   => 'alert-info'
            };
            echo '<div class="alert ' . $class . '">'
                . htmlspecialchars($flash['message'])
                . '<span class="close-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span>'
                . '</div>';
        }
        echo '</div>';

        // Clear messages after display
        unset($_SESSION['flash_messages'][$page]);
    }
}
