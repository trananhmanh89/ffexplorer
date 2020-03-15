export const arrange = function(items) {
    items.sort((a, b) => {
        if (a.type !== b.type) {
            return a.type === 'folder' ? -1 : 1;
        }

        const min = Math.min(a.name.length, b.name.length);

        for (let i = 0; i < min; i++) { 
            const aSub = a.name.toLowerCase().substring(i, 1);
            const bSub = b.name.toLowerCase().substring(i, 1);

            if (aSub !== bSub) {
                return aSub < bSub ? -1 : 1;
            }
        }

        return a.name.length < b.name.length ? - 1 : 1;
    });

    return items;
}

export const translate = function(text) {
    const {language} = FF_EXPLORER_DATA;
    return language[text] || text;
}