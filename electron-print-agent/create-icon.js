const fs = require('fs');
const path = require('path');
// const { createCanvas } = require('canvas'); // Not needed for base64 write

function createIcon() {
    // Basic dependency check - on some systems canvas might need build tools
    // We will try to make a very simple buffer if canvas fails or just generic file
    // Check if we can just download one or use a base64 string

    // Simplest: Base64 to file
    const base64Icon = `iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAHASURBVHhe7Zs9SgNBEIbnWbDxFoxgkTQ2OgsrD2BleQMLCwsP4A28hY0nsLSwCCF/IzuzC4skjV/wJjOfPyyzC7M7298yI0mSJEmSJEmSJEkf0+l0MpvN/jQYDL6C+/B5PB6/h9/g8/PnZ6y1tW86nc5yOBy2wX14P51O38P78Hk+n7+H3+D9brfbWq/X/4T78v0wDFe73e4qvA+fw+GwDe/D52Kx+JjP5/vD4bAd3geDweAr3Jfx+C0ck+12u0uM6fF43AbbYIxtMAzD1eFw2AbbYBtsg22wDbbBNtgG22AbbINtsA22wTbYBttgG2yDbbANtsE22AbbYBttgG2wDbbBNtgG22AbbINtsA22wTbYBttgG2yDbbANtsE22AbbYBttgG2wDbbBNtgG22AbbGOM7T8xxtgG2xjaBttgjG0wjG2wDbbBNtgG22AbbINtsA22wTbYBttgG2yDbbANtsE22AbbYBttgG2wDbbBNtgG22AbbINtsA22wTbYBttgG2yDbbANtsE22AbbYBttgG2wDbbBNtgG22AbbGM/Y4xtsI2hbfwVxtgGw9gG25AkSZIkSZIkSeqr5/MFAeY6+18xK94AAAAASUVORK5CYII=`;

    const buffer = Buffer.from(base64Icon, 'base64');
    fs.writeFileSync(path.join(__dirname, 'assets', 'icon.png'), buffer);
    console.log('Icon created at assets/icon.png');
}

createIcon();
