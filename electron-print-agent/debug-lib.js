const lib = require('node-thermal-printer');
console.log('Exports:', Object.keys(lib));
if (lib.drivers) {
    console.log('Drivers:', Object.keys(lib.drivers));
} else {
    console.log('No drivers property exposed directly.');
}

try {
    const linuxDriver = require('node-thermal-printer/lib/drivers/linux');
    console.log('Linux driver found via direct path');
} catch (e) {
    console.log('Cannot require linux driver directly:', e.message);
}
