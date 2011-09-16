
    /**
     * Finds and displays a {{ entity }} document.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/show", name="{{ route_name_prefix }}_show")
     * @Template()
{% endif %}
     */
    public function showAction($id)
    {
        $dm = $this->get('doctrine.odm.mongodb.default_document_manager');

        $document = $dm->getRepository('{{ bundle }}:{{ entity }}')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Unable to find {{ entity }} document.');
        }
{% if 'delete' in actions %}

        $deleteForm = $this->createDeleteForm($id);
{% endif %}

{% if 'annotation' == format %}
        return array(
            'document'      => $document,
{% if 'delete' in actions %}
            'delete_form' => $deleteForm->createView(),

{%- endif %}
        );
{% else %}
        return $this->render('{{ bundle }}:{{ entity|replace({'\\': '/'}) }}:show.html.twig', array(
            'document'      => $document,
{% if 'delete' in actions %}
            'delete_form' => $deleteForm->createView(),

{% endif %}
        ));
{% endif %}
    }
