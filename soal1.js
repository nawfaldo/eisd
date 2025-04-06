const input = [5,4,2.5,5,3.6,1.1,3.6,4,4.2,1.5];

function idk(arr) {
    return [
        Math.max(...arr), 
        Math.min(...arr), 
        arr.reduce((sum, num) => sum + num, 0) / arr.length
    ];
}

console.log(idk(input))
