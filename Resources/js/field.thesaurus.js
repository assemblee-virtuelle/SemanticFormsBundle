class VirtualAssemblyFieldThesaurus  extends VirtualAssemblyFieldUri {
    getUriLabel(uri, complete) {
        if (uri) {
            $.ajax({
                url: this.urlLabel,
                data: {
                    uri: uri,
                    graphUri: this.$.attr('data-sf-graphuri'),
                },
                complete: (r) => {
                    this.setValue(uri, r.responseJSON.label);
                }
            });
        }
    }
    lookupParams(params) {
        return {
            rdfType: this.rdfType,
            QueryString: params.term,
            graphUri: this.$.attr('data-sf-graphuri'),
            MaxHits: 15
        };
    }
}


