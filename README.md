VirtualAssembly/SemanticFormsBundle 
===============

This is a SemanticForms bundle in WORK IN PROGRESS. 
There is not a stable version for the moment.

There aim is to play the "interface" between the different services of a semantic forms instance and a symfony project.
To have more information on semantic forms : please see https://github.com/jmvanel/semantic_forms

How to use it 
===============
1) download
If you want to test this bundle, you need to add in your main composer :
- in require :

    "VirtualAssembly/SemanticFormsBundle" : "dev-master"
    
- create a repositories section at the same level of require

	"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/assemblee-virtuelle/SemanticFormsBundle.git"
        }
    ],
2) in your parameters.yml or on your config.yml --> section parameters
You need to set this variable :
    - semantic_forms.domain: #the adress to access at your semantic forms instace
    - semantic_forms.login: #defalut user account
    - semantic_forms.password: # password of the default user
    - semantic_forms.timeout: # timeout
    - semantic_forms.base_url_form: #this is for set the base url form like 'http://xmlns.com/foaf/0.1/' and after use that to create youtr own form
