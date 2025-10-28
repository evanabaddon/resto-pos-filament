<?php

return [
    'printer' => [
        'type' => env('PRINTER_TYPE', 'usb'),
        'usb_name' => env('USB_PRINTER_NAME', 'POS-58'),
        'network_ip' => env('NETWORK_PRINTER_IP', '192.168.1.100'),
        'network_port' => env('NETWORK_PRINTER_PORT', 9100),
    ],
];