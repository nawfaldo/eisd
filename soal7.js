function decryptMessage(encryptedText) {
    return encryptedText.replace(/[a-z]/g, (char) => {
        let index = char.charCodeAt(0) - 96;
        
        let newIndex = index - 5;
        
        if (newIndex < 1) {
            newIndex += 26;
        }
        
        return String.fromCharCode(newIndex + 96);
    });
}
const encryptedMessages = [
    "xfqfr bfmdz",
    "gjxtp lzj rfz ifkyfw jxi snm",
    "gwt, gjxtp qz rfz rfpfs in bfwlty lfp?",
    "fpz xfdfsl pfrz, rfz lfp ofin ufhfwpz",
    "dfsl pnwnr xynhpjw otrtp pz pnhp ifwn lwzu"
];

const decryptedMessages = encryptedMessages.map(decryptMessage);

decryptedMessages.forEach((message, index) => {
    console.log(`Message ${index + 1}:`, message);
});
