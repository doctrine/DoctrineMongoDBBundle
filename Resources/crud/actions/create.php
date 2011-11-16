
    /**
     * Creates a new {{ entity }} document.
     *
{% if 'annotation' == format %}
     * @Route("/create", name="{{ route_name_prefix }}_create")
     * @Method("post")
     * @Template("{{ bundle }}:{{ entity }}:new.html.twig")
{% endif %}
     */
    public function createAction()
    {
        $document  = new {{ entity_class }}();
        $request = $this->getRequest();
        $form    = $this->createForm(new {{ entity_class }}Type(), $document);
        $form->bindRequest($request);

        if ($form->isValid()) {
            $dm = $this->get('doctrine.odm.mongodb.default_document_manager');
            $dm->persist($document);
            $dm->flush();

            {% if 'show' in actions -%}
                return $this->redirect($this->generateUrl('{{ route_name_prefix }}_show', array('id' => $document->getId())));
            {% else -%}
                return $this->redirect($this->generateUrl('{{ route_name_prefix }}'));
            {%- endif %}

        }

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
