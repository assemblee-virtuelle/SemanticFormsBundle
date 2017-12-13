class VirtualAssemblyFieldMultiple  {
    lookupProcessResults(data) {
        let label = $.trim(data.term);
        this.selectValues = {};
        this.selectValues[label] = label;
        return {
            id: label,
            text: label,
            newTag: true
        };
    }

    getUriLabel(uri, complete) {
        this.setValue(uri, uri);

    }
    lookupParams(params) {
        return {
            q: params.term,
            MaxHits: 15
        };
    }

    constructor(dom) {
        this.value = {};
        this.$ = $(dom);
        this.$selector = this.$.find('.tags-selector');
        this.$value = this.$.find('.tags-value');
        this.$tags = this.$.find('.tags');
        this.urlLookup = this.$.attr('data-sf-lookup');
        this.urlLabel = this.$.attr('data-sf-label');
        this.rdfType = this.$.attr('data-sf-rdfType');
        this.$selector.select2({
            width: '100%',
            placeholder: "Ajoutez un terme ici",
            tags:true,
            createTag:this.lookupProcessResults.bind(this),
        });
        this.$selector.on("select2:select", (e) => {
            log("yolo");
            this.setValue(this.$selector.val(), this.selectValues[this.$selector.val()]);
        });

        // Get value.
        let startupValue = this.$value.val();
        // Parse it.
        startupValue = startupValue && JSON.parse(startupValue);
        let getLabelCallback = (label, uri) => {
            this.setValue(uri, label)
        };
        // Load it.
        if (Array.isArray(startupValue)) {
            $.each(startupValue, (key, uri) => {
                // Avoid all kind of empty fields.
                if (uri) {
                    this.getUriLabel(uri, getLabelCallback);
                }
            });
        }
    }

    setValue(uri, text) {
        log(uri);
        log(text);
        // Add to values.
        this.value[uri] = text;
        // Reload list.
        this.fillValues();
    }

    fillValues() {
        this.$value.val(JSON.stringify(this.value));
        this.$tags.empty();
        $.each(this.value, (uri, text) => {
            if (text !== false){
                let $item = $('<span class="tag">' + text + ' <a href="#" class="remove-tag glyphicon glyphicon-remove"></a></span>')
                // Click event.
                $item.find('a.remove-tag').click((e) => {
                    e.preventDefault();
                    delete this.value[uri];
                    this.fillValues();
                });
                // Append.
                this.$tags.append($item);
            }
        });
        this.$tags.append('<div class="clearfix"></div>');
        // Show tags section if not empty.
        this.$tags.toggle(!!Object.keys(this.value).length);
        // Clear selector.
        this.$selector.val(null).trigger("change");
    }
}


