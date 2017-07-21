VirtualAssembly/SemanticFormsBundle 
===============

This is a SemanticForms bundle in WORK IN PROGRESS. 
There is not a stable version for the moment.

There aim is to play the "interface" between the different services of a semantic forms instance and a symfony project.
To have more information on semantic forms : please see https://github.com/jmvanel/semantic_forms

How to use it 
===============

If you want to test this bundle, you need to add in your main composer :
- in require :

    "VirtualAssembly/SemanticFormsBundle" : "dev-master"
    
- create a repositories section at the same level of require

	"repositories": [
        {
            "type": "vcs",
            "url": ""
        }
    ],