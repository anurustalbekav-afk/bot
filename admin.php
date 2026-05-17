<?php
// Совместимость со старой ссылкой /admin.php — теперь админка живёт в /admin/.
header('Location: /admin/', true, 301);
exit;
