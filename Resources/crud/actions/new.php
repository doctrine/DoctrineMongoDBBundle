
    /**
     * Displays a form to create a new {{ entity }} document.
     *
{% if 'annotation' == format %}
     * @Route("/new", name="{{ route_name_prefix }}_new")
     * @Template()
{% endif %}
     */
    public function newAction()
    {
        $document = new {{ entity_class }}();
        $form   = $this->createForm(new {{ entity_class }}Type(), $document);

{% if 'annotation' == format %}
        return array(
            'document' => $document,
            'form'   => $form->createView()
        );
{% else %}
        return $this->render('{{ bundle }}:{{ entity|replace({'\\': '/'}) }}:new.html.twig', array(
            'document' => $document,
            'form'   => $form->createView()
        ));
{% endif %}
    }
