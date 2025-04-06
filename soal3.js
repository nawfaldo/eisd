function siapaPencuri() {
    const tamu = ["Ningguang", "Hutao", "Xiao", "Childe"];
    const buktiTerakhir = "Xiao"; // Foto Xiao menunjukkan kue masih ada

    // Childe masuk terakhir setelah bukti terakhir
    const pencuri = tamu[tamu.indexOf(buktiTerakhir) + 1];

    return `${pencuri} kemungkinan besar mengambil kue!`;
}

console.log(siapaPencuri()); 
