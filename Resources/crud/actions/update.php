
    /**
     * Edits an existing {{ entity }} document.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/update", name="{{ route_name_prefix }}_update")
     * @Method("post")
     * @Template("{{ bundle }}:{{ entity }}:edit.html.twig")
{% endif %}
     */
    public function updateAction($id)
    {
        $dm = $this->get('doctrine.odm.mongodb.default_document_manager');

        $document = $dm->getRepository('{{ bundle }}:{{ entity }}')->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Unable to find {{ entity }} document.');
        }

        $editForm   = $this->createForm(new {{ entity_class }}Type(), $document);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bindRequest($request);

        if ($editForm->isValid()) {
            $dm->persist($document);
            $dm->flush();

            return $this->redirect($this->generateUrl('{{ route_name_prefix }}_edit', array('id' => $id)));
        }

{% if 'annotation' == format %}
        return array(
            'document'      => $document,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
{% else %}
        return $this->render('{{ bundle }}:{{ entity|replace({'\\': '/'}) }}:edit.html.twig', array(
            'document'      => $document,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
{% endif %}
    }
