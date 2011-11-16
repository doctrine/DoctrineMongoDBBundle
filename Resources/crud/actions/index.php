
    /**
     * Lists all {{ entity }} entities.
     *
{% if 'annotation' == format %}
     * @Route("/", name="{{ route_name_prefix }}")
     * @Template()
{% endif %}
     */
    public function indexAction()
    {
        $dm = $this->get('doctrine.odm.mongodb.default_document_manager');

        $documents = $dm->getRepository('{{ bundle }}:{{ entity }}')->findAll();

{% if 'annotation' == format %}
        return array('documents' => $documents);
{% else %}
        return $this->render('{{ bundle }}:{{ entity|replace({'\\': '/'}) }}:index.html.twig', array(
            'documents' => $documents
        ));
{% endif %}
    }
