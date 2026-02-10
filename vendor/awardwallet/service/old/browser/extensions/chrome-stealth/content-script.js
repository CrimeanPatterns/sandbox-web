'use strict'

console.log('CS: hiding selenium');

const scriptElement = document.createElement('script');
scriptElement.innerHTML = PAGE_SCRIPT;
document.documentElement.prepend(scriptElement);

console.log('CS: hide-selenium complete');
