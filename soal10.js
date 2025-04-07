function hitungKembalian(totalPembayaran, totalBelanja) {
    let kembalian = totalPembayaran - totalBelanja;
    const pecahan = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100];
    let hasil = {};

    for (let uang of pecahan) {
        if (kembalian >= uang) {
            hasil[uang] = Math.floor(kembalian / uang);
            kembalian %= uang;
        }
    }

    return hasil;
}

console.log(hitungKembalian(10000, 7500)); 

console.log(hitungKembalian(5000, 1100)); 

console.log(hitungKembalian(178000, 90500)); 
