function findNaughtyKids(names) {
    const count = names.reduce((acc, name) => (acc[name] = (acc[name] || 0) + 1, acc), {});

    const maxFreq = Math.max(...Object.values(count));
    
    if (maxFreq === 1) return "Semuanya anak baik";

    const naughtyKids = Object.entries(count)
        .filter(([_, freq]) => freq === maxFreq)
        .map(([name]) => name);

    return `${naughtyKids.join(" dan ")} Nackal`;
}

console.log(findNaughtyKids(["Bagas", "Dimas", "Bagas", "Bagas", "Indra", "Gilang", "Gilang", "Hana", "Fajar", "Fajar"]));

console.log(findNaughtyKids(["Bagas", "Dimas", "Fajar", "Bagas", "Indra", "Gilang", "Gilang", "Bagas", "Fajar", "Fajar"]));

console.log(findNaughtyKids(["Aisyah", "Bagas", "Dewi", "Dimas", "Eka", "Fajar", "Gilang", "Hana", "Indra", "Jihan"]));
