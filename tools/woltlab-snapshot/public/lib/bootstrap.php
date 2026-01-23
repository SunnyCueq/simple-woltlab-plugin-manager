<?php /* 2026-01-08T08:26:27+00:00 */

return (function() {
    if (\ENABLE_DEBUG_MODE) {
        $shuffle = static function (array $array) {
            \shuffle($array);

            return $array;
        };
    } else {
        $shuffle = static function (array $array) {
            return $array;
        };
    }

    return [
        require(__DIR__ . '/bootstrap/com.woltlab.wcf.php'),
    ];
})();
