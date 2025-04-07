function recommendProducts(customerName, customers, products) {
    const customer = customers.find(c => c.name === customerName);
    if (!customer) return [];

    const { interest, bought } = customer;

    const recommended = products
        .filter(p => interest.includes(p.category))
        .map(p => p.name);

    return [...new Set([...bought, ...recommended])];
}

const products = [
    { name: "TV", category: "elektronik", price: 1000 },
    { name: "headphone", category: "elektronik", price: 200 },
    { name: "baju", category: "fashion", price: 50 },
    { name: "gitar", category: "musik", price: 300 },
    { name: "sepatu", category: "olahraga", price: 80 },
    { name: "kamera", category: "elektronik", price: 600 }
];

const customers = [
    { name: "Rina", interest: ["elektronik", "musik"], bought: ["TV", "headphone"] },
    { name: "Budi", interest: ["fashion", "musik"], bought: ["baju", "gitar"] },
    { name: "Hartono", interest: ["olahraga", "elektronik"], bought: ["sepatu", "kamera"] }
];

console.log(recommendProducts("Rina", customers, products));
