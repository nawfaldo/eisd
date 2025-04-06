const menu = {
    "Ayam Goreng Krispi": { price: 15000, type: "makanan" },
    "Ayam Puk Puk": { price: 13000, type: "makanan" },
    "Ayam Bakar": { price: 20000, type: "makanan" },
    "Es Teh": { price: 5000, type: "minuman" },
    "Es Jeruk": { price: 7000, type: "minuman" }
};

const ITEM_TAX = { makanan: 0.05, minuman: 0.03 };
const TRANSACTION_TAX = 0.15;

function idk(order) {
    let subtotal = 0;
    
    for (const [item, quantity] of Object.entries(order)) {
        if (menu[item]) {
            let { price, type } = menu[item];
            let tax = price * ITEM_TAX[type];
            let finalPrice = price + tax;
            subtotal += finalPrice * quantity;
        }
    }
    
    let total = subtotal + (subtotal * TRANSACTION_TAX);
    return Math.round(total);
}

const orders = {
    "Rehan Whangsap": { "Ayam Bakar": 2, "Es Teh": 1 },
    "Amba Roni": { "Ayam Puk Puk": 1, "Es Teh": 3 },
    "Faiz Ngawi": { "Ayam Goreng Krispi": 1, "Ayam Puk Puk": 1, "Ayam Bakar": 1, "Es Teh": 1, "Es Jeruk": 1 }
};

for (const [name, order] of Object.entries(orders)) {
    console.log(`${name}: Rp${idk(order).toLocaleString("id-ID")}`);
}
