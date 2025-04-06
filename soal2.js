function idk(str) {
    str = str.toLowerCase().replace(/[^a-z0-9]/g, '');
    let left = 0, right = str.length - 1;

    while (left < right) {
        if (str[left] !== str[right]) {
            console.log("suka blyat");
            return;
        }
        left++;
        right--;
    }
    console.log("eureeka!");
}

idk("Aku Suka Kamu");
idk("Ibu Ratna antar ubi.")
