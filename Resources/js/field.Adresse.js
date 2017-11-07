class VirtualAssemblyFieldAdresse extends VirtualAssemblyFieldUri {
    lookupProcessResults(data) {
        let items = [];
        this.selectValues = {};
        for (let result of data.features) {
            items.push({
                id: result.properties.label,
                text: result.properties.label
            });
            this.selectValues[result.properties.label] = result.properties.label;
        }
        return {
            results: items
        };
    }

    getUriLabel(uri, complete) {
        if (uri) {
            $.ajax({
                url: 'https://api-adresse.data.gouv.fr/search/',
                data: {
                    q: uri
                },
                complete: (r) => {
                let array =  r.responseJSON.features;
            if (array.length > 0){
                let elem = array[0];
                this.setValue(uri, elem.properties['label']);
            }

        }
        });
        }
    }
    lookupParams(params) {
        return {
            q: params.term,
            MaxHits: 15
        };
    }
}


