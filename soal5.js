const input = "Naip Lovyu";

function idk(str) {
    const result = new Set();
    const maxLength = 6;
    
    function generate(current, remaining) {
        if (current.length > 0 && current.length <= maxLength) {
            result.add(current);
        }
        for (let i = 0; i < remaining.length; i++) {
            generate(current + remaining[i], remaining.slice(0, i) + remaining.slice(i + 1));
        }
    }
    
    generate("", str.toLowerCase().replace(/\s+/g, ''));
    
    return result.size;
}

console.log(idk(input));
