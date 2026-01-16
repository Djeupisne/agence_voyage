<?php
   echo "Hash for admin123: " . password_hash('admin123', PASSWORD_DEFAULT) . "<br>";
   echo "Hash for client123: " . password_hash('client123', PASSWORD_DEFAULT);
   ?>