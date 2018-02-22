<?php

namespace VirtualAssembly\SemanticFormsBundle\Form;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VirtualAssembly\SemanticFormsBundle\SemanticFormsBundle;
use VirtualAssembly\SemanticFormsBundle\Services\SemanticFormsClient;

abstract class SemanticFormType extends AbstractType
{
    const FIELD_ALIAS_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

    /**
     * @var array
     */
    var $formSpecification = [];
    var $formValues = [];
    var $fieldsAdded = [];
    var $fieldsAliases = [];
    var $uri;
    var $conf;

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'client'   => '',
                'login'    => '',
                'password' => '',
                'graphURI' => '',
                'values'   => '',
                'spec'     => '',
                'role'     => '',
                'sfConf'     => '',
            )
        );
    }

    function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \VirtualAssembly\SemanticFormsBundle\Services\SemanticFormsClient $client */
        $client = $options['client'];
        // Get credential for semantic forms auth.
        $login    = $options['login'];
        $password = $options['password'];
        $graphURI = $options['graphURI'];
        $editMode = !!$options['values'];
        $sfConf = $options['sfConf'];
        $this->fieldsAliases = $sfConf['fields'];
        $this->conf = $sfConf;

        // We have an uri (edit mode).
        if ($editMode) {
            $formSpecificationRaw = $client->formData(
                $options['values'],
                $options['spec']
            );
            $uri                  = $options['values'];
        } // Create mode.
        else {
            $formSpecificationRaw = $client->createData(
                $options['spec']
            );
            $uri                  = $formSpecificationRaw['subject'];
            if($graphURI == null ){
                $graphURI =  $formSpecificationRaw['subject'];
            }
        }
        $this->uri = $uri;
        // Create from specification.
        $formSpecification = [];
        foreach ($formSpecificationRaw['fields'] as $field) {
            $localHtmlName = $this->getLocalHtmlName($field['property']);
            // First value of this type of field.
            if (!isset($formSpecification[$localHtmlName])) {
                // Save into field spec.
                $field['localHtmlName'] = $localHtmlName;
                // Register with name as key.
                $formSpecification[$localHtmlName] = $field;
            }
            // Manage multiple fields.
            $fieldSaved = $formSpecification[$localHtmlName];
            // Turn field value to array,
            // and use htmlName as key for eah value.
            if (!is_array($fieldSaved['value'])) {
                $fieldSaved['value'] = [ $fieldSaved['value']];
            }
            // Push new value.
            $fieldSaved['value'][] = $field['value'];
            // Html name is base on the value of field (not only the type)
            // So we remove it in case on multiple values.
            $fieldSaved['value'] = array_filter(array_unique($fieldSaved['value']));
            unset($fieldSaved['htmlName']);
            // Save field.
            $formSpecification[$localHtmlName] = $fieldSaved;
        }

        $this->formSpecification = $formSpecification;
        //dump($this->formSpecification); //exit;

        // Manage form submission.
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use (
                $client,
                $editMode,
                $uri,
                $login,
                $password,
                $graphURI,
                $sfConf
            ) {
                $client->auth($login,$password);
                $form = $event->getForm();
                // Add uri for external usage.
                $form->uri = $uri;
                $client->update("INSERT DATA { GRAPH <".$graphURI."> { <".$this->uri ."> <".self::FIELD_ALIAS_TYPE."> <".$sfConf['type'].">.}}");

                if (array_key_exists('otherType',$sfConf)){
                    if(is_array($sfConf['otherType'])){
                        foreach ($sfConf['otherType'] as $type)
                            $client->update("INSERT DATA { GRAPH <".$graphURI."> { <".$this->uri ."> <".self::FIELD_ALIAS_TYPE."> <".$type.">.}}");
                    }else{
                        $client->update("INSERT DATA { GRAPH <".$graphURI."> { <".$this->uri ."> <".self::FIELD_ALIAS_TYPE."> <".$sfConf['otherType'].">.}}");
                    }
                }
                //}
                $arrayTest= [];

                foreach ($this->fieldsAdded as $localHtmlName) {
                    $fieldSpec    = $this->formSpecification[$localHtmlName];
                    $arrayTest[$localHtmlName] = $this->getContentToUpdate(
                        $fieldSpec['localType'],
                        $form->get($localHtmlName)->getData(),
                        $fieldSpec['value']
                    );
                }
                $havetodelete = $havetoinsert= false;
                //delete
                $deleteQuery =$insertQuery= '';

                foreach ($arrayTest as $localhtmlname => $content){
                    $mainPredicat = $this->formSpecification[$localhtmlname]["property"];
                    $predicatArray = [$mainPredicat];
                    if(array_key_exists('otherPredicat',$sfConf['fields'][$mainPredicat]) && $sfConf['fields'][$mainPredicat]['otherPredicat'] ){
                        $predicatArray = array_merge($predicatArray,$sfConf['fields'][$mainPredicat]['otherPredicat']);
                    }

                    //delete
                    if($content['delete']){
                        if(!$havetodelete){
                            $deleteQuery = "DELETE { GRAPH <".$graphURI.'> { ';
                        }
                        $havetodelete = true;
                        foreach ($predicatArray as $predicat){
                            foreach ($content['delete'] as $data => $type){
                                $deleteQuery.= "<".$this->uri."> <".$predicat.'> ';
                                if($type =="uri"){
                                    $deleteQuery.='<'.$data.'>. ';
                                }
                                else{
                                    $deleteQuery.='?o.';
                                }
                            }
                        }
                    }
                    //insert
                    if($content['insert']){
                        if(!$havetoinsert){
                            $insertQuery = "INSERT DATA { GRAPH <".$graphURI.'> { ';
                        }
                        $havetoinsert = true;
                        foreach ($predicatArray as $predicat){
                            foreach ($content['insert'] as $data => $type){
                                if($data && !strstr($data,"{}") && !strstr($data,"[]")) {
                                    $insertQuery .= "<" . $this->uri . "> <" . $predicat . '> ';
                                    if ($type == "uri") {
                                        $insertQuery .= '<' . $data . '>. ';
                                    } else {
                                        $insertQuery .= '"""' . $data . '""". ';

                                    }
                                }
                            }
                        }
                    }
                }
                if($havetodelete){
                    $deleteQuery .= "}} WHERE { GRAPH <".$graphURI."> { <".$this->uri."> ?P ?o }} ";
                }
                if($havetoinsert){
                    $insertQuery .= "}}";
                }
                //dump($deleteQuery);
                //dump($insertQuery);exit;
                $client->update($deleteQuery);
                $client->update($insertQuery);
                $reverse = $sfConf['reverse'];
                if(!is_null($reverse)){
                    $values = array();
                    foreach ($reverse as $key=>$elem){
                        $localHtmlName = $this->fieldsAliases[$key]['value'];
                        if (array_key_exists($elem,$values))
                            $values[$elem] = array_merge($values[$elem],json_decode($form->get($localHtmlName)->getData(),JSON_OBJECT_AS_ARRAY));
                        else
                            $values[$elem] = json_decode($form->get($localHtmlName)->getData(),JSON_OBJECT_AS_ARRAY);

                    }
                    $this->update($graphURI,$this->uri,$values,$client,$reverse);
                }
            }
        );
    }

    private function getContentToUpdate($localtype,$dataSubmitted,$oldData){
        //$outputSingleValue = $dataSubmitted;
        //dump($dataSubmitted);


        if ($dataSubmitted) {
            switch ($localtype) {

                // Date
                case 'Symfony\Component\Form\Extension\Core\Type\DateType':
                case 'Symfony\Component\Form\Extension\Core\Type\DateTimeType':
                    /** @var $value \DateTime */
                    $dataSubmitted = $dataSubmitted->format('Y-m-d H:i:s');
                    break;

                // Uri
                case 'VirtualAssembly\SemanticFormsBundle\Form\UriType':
                    // DbPedia
                case 'VirtualAssembly\SemanticFormsBundle\Form\DbPediaType':
                case 'VirtualAssembly\SemanticFormsBundle\Form\ThesaurusType':


                if(json_decode($dataSubmitted, JSON_OBJECT_AS_ARRAY))
                        $dataSubmitted = json_decode($dataSubmitted, JSON_OBJECT_AS_ARRAY);
                    $insert = $delete = [];
                    if (is_array($dataSubmitted)) {
                        foreach (array_keys($dataSubmitted) as $data){
                            // if(!in_array($data,$oldData)){
                            $insert[$data] ="uri";
                            // }
                        }

                    }else{
                        $insert[$dataSubmitted] ="uri";
                    }
                    foreach ($oldData as $data){
                        //if(!array_key_exists($data,$dataSubmitted)){
                        $delete[$data] ="text";
                        //}
                    }

                    return ['insert' => $insert, 'delete' => $delete];
                    break;
                // adresse
                case 'VirtualAssembly\SemanticFormsBundle\Form\AdresseType':
                case 'VirtualAssembly\SemanticFormsBundle\Form\MultipleType':
                    if(json_decode($dataSubmitted, JSON_OBJECT_AS_ARRAY))
                        $dataSubmitted = json_decode($dataSubmitted, JSON_OBJECT_AS_ARRAY);
                    $insert = $delete = [];
                    if (is_array($dataSubmitted)) {
                        foreach (array_keys($dataSubmitted) as $data){
                            // if(!in_array($data,$oldData)){
                            $insert[$data] ="text";
                            // }
                        }

                    }else{
                        $insert[$dataSubmitted] ="text";
                    }
                    foreach ($oldData as $data){
                        //if(!array_key_exists($data,$dataSubmitted)){
                        $delete[$data] ="text";
                        //}
                    }
                    return ['insert' => $insert, 'delete' => $delete];
                    break;
            }
        }

        if(current($oldData) != $dataSubmitted){
            $removeOldData = [];
            foreach ($oldData as $data){
                $removeOldData[$data] = 'text';
            }
            return ['insert' => [$dataSubmitted => "text"]  , 'delete' => $removeOldData];
        }
        else{
            return ['insert' => null , 'delete' => null];
        }

    }


    public function add(
        FormBuilderInterface $builder,
        $localHtmlName,
        $type = null,
        $options = []
    ) {
        if (!isset($this->formSpecification[$localHtmlName])) {
            throw new Exception(
                'Form field not found into specification '.$localHtmlName
            );
        }

        if (isset($this->formSpecification[$localHtmlName]['value'])) {
            // Label.
            $options['label'] = $this->formSpecification[$localHtmlName]['label'];
            // Get value.
            $options['data']   = $this->fieldDecode(
                $type,
                $this->formSpecification[$localHtmlName]['value']
            );
            $options['mapped'] = false;
        }
        // Save local field type for encoding before post.
        $this->formSpecification[$localHtmlName]['localType'] = $type;
        $this->fieldsAdded[]                                  = $localHtmlName;
        $builder->add($localHtmlName, $type, $options);

        return $this;
    }

    function buildHtmlName($subject, $predicate, $value,$test = false)
    {

        if($test){
            return urlencode(
            // Concatenate : <S> <P> <"O">.
                '<'.implode('> <', [$subject, $predicate]).'> "'.$value.'"@en .'
            );
        }
        else{
            return urlencode(
            // Concatenate : <S> <P> <"O">.
                '<'.implode('> <', [$subject, $predicate, ''.$value.'']).'>.'
            );
        }
    }

    /**
     * From semantic forms to front.
     */
    public function fieldDecode($type, $values)
    {
        switch ($type) {
            // Date
            case 'Symfony\Component\Form\Extension\Core\Type\DateType':
            case 'Symfony\Component\Form\Extension\Core\Type\DateTimeType':
                try{
                    return new \DateTime(current($values));
                }
                catch (\Exception $e){
                    return new \DateTime();
                }
                break;

            // Number
            case 'Symfony\Component\Form\Extension\Core\Type\NumberType':
                return (float) current($values);

            // Uri
            case 'VirtualAssembly\SemanticFormsBundle\Form\UriType':
                // adresse
            case 'VirtualAssembly\SemanticFormsBundle\Form\AdresseType':
                // DbPedia
            case 'VirtualAssembly\SemanticFormsBundle\Form\DbPediaType':
            case 'VirtualAssembly\SemanticFormsBundle\Form\MultipleType':
            case 'VirtualAssembly\SemanticFormsBundle\Form\ThesaurusType':

                // Keep only links.
                return is_array($values) ? json_encode(
                    array_values($values),
                    JSON_OBJECT_AS_ARRAY
                ) : [];
                break;
        }

        // We take the last version of the value.
        return current($values);
    }


    function getLocalHtmlName($htmlName)
    {
        if (isset($this->fieldsAliases[$htmlName])) {
            return $this->fieldsAliases[$htmlName]['value'];
        } else {
            return $htmlName;
        }
    }


    private function update($graph,$subject,$values,$sfClient,$reverse){

        //supprimer tous les précédent liens
        foreach ($reverse as $key=>$elem){
            $query="DELETE { GRAPH ?gr { ?s <".$elem."> ".$sfClient->formatValue(SemanticFormsClient::VALUE_TYPE_URI,$subject)." . }} WHERE { GRAPH ?gr { ?s <".$elem."> ".$sfClient->formatValue(SemanticFormsClient::VALUE_TYPE_URI,$subject)." .}}";
            $sfClient->update($query);
        }
        //loop sur les nouveaux liens
        foreach ($values as $predicat=>$elems){
            if($elems){
                foreach ($elems as $link=>$elem){
                    if (!is_integer($link)){
                        $query="INSERT { GRAPH ?GR { <".$link."> <".$predicat."> ".$sfClient->formatValue(SemanticFormsClient::VALUE_TYPE_URI,$subject)." . }} WHERE {GRAPH ?GR { <".$link."> ?p ?o .}}";
                        $sfClient->update($query);
                    }
                }
            }
        }
    }
}
