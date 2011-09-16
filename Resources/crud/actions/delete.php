
    /**
     * Deletes a {{ entity }} document.
     *
{% if 'annotation' == format %}
     * @Route("/{id}/delete", name="{{ route_name_prefix }}_delete")
     * @Method("post")
{% endif %}
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);

        if ($form->isValid()) {
            $dm = $this->get('doctrine.odm.mongodb.default_document_manager');
            $document = $dm->getRepository('{{ bundle }}:{{ entity }}')->find($id);

            if (!$document) {
                throw $this->createNotFoundException('Unable to find {{ entity }} document.');
            }

            $dm->remove($document);
            $dm->flush();
        }

        return $this->redirect($this->generateUrl('{{ route_name_prefix }}'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }