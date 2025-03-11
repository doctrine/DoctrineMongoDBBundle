Creating a Registration Form
============================

Some forms have extra fields whose values don't need to be stored in the
database. In this example, we'll create a registration form with such
field ("terms accepted" checkbox field) and embed the form that actually
stores the account information. We'll use MongoDB for storing the data.

The User Model
---------------------

We begin this tutorial with the model for a ``User`` document:

.. configuration-block::

    .. code-block:: php-attributes

        // src/Document/User.php
        namespace App\Document;

        use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
        use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
        use Symfony\Component\Validator\Constraints as Assert;

        #[ODM\Document(collection: 'users')]
        #[ODM\Unique(fields: 'email')]
        class User
        {
            #[ODM\Id]
            protected string $id;

            #[ODM\Field(type: 'string')]
            #[Assert\NotBlank]
            #[Assert\Email]
            protected ?string $email = null;

            #[ODM\Field(type: 'string')]
            #[Assert\NotBlank]
            protected ?string $password = null;

            public function getId(): string
            {
                return $this->id;
            }

            public function getEmail(): ?string
            {
                return $this->email;
            }

            public function setEmail(?string $email): void
            {
                $this->email = $email;
            }

            public function getPassword(): ?string
            {
                return $this->password;
            }

            // stupid simple encryption (please don't copy it!)
            public function setPassword(?string $password): void
            {
                $this->password = sha1($password);
            }
        }

This ``User`` document contains three fields and two of them (email and
password) should be displayed in the form. The email property must be unique
in the database, so we've added this validation at the top of the class.

.. note::

    If you want to integrate this User within the security system, you need
    to implement the ``UserInterface`` of the `Security component`_.

Create a Form for the Model
---------------------------

Next, create the form for the ``User`` model:

.. code-block:: php

    // src/Form/Type/UserType.php
    namespace App\Form\Type;

    use App\Document\User;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\EmailType;
    use Symfony\Component\Form\Extension\Core\Type\PasswordType;
    use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class UserType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder->add('email', EmailType::class);
            $builder->add('password', RepeatedType::class, [
               'first_name' => 'password',
               'second_name' => 'confirm',
               'type' => PasswordType::class
            ]);
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults([
                'data_class' => User::class,
            ]);
        }
    }

We added two fields: email and password (repeated to confirm the entered
password). The ``data_class`` option tells the form the name of the class
that holds the underlying data (i.e. your ``User`` document).

.. tip::

    To explore more things about the Form component, read its `documentation`_.

Embedding the User form into a Registration Form
------------------------------------------------

The form that you'll use for the registration page is not the same as the
form used to modify the ``User`` (i.e. ``UserType``). The registration
form will contain further fields like "accept the terms", whose value won't be
stored in the database.

In other words, create a second form for registration, which embeds the ``User``
form and adds the extra field needed:

.. code-block:: php

    // src/Form/Model/Registration.php
    namespace App\Form\Model;

    use App\Document\User;
    use Symfony\Component\Validator\Constraints as Assert;

    class Registration
    {
        /**
         * @Assert\Type(type="App\Document\User")
         */
        protected $user;

        /**
         * @Assert\NotBlank()
         * @Assert\IsTrue()
         */
        protected $termsAccepted;

        public function setUser(User $user)
        {
            $this->user = $user;
        }

        public function getUser()
        {
            return $this->user;
        }

        public function getTermsAccepted()
        {
            return $this->termsAccepted;
        }

        public function setTermsAccepted($termsAccepted)
        {
            $this->termsAccepted = (bool) $termsAccepted;
        }
    }

Next, create the form for this ``Registration`` model:

.. code-block:: php

    // src/Form/Type/RegistrationType.php
    namespace App\Form\Type;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
    use Symfony\Component\Form\FormBuilderInterface;

    class RegistrationType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder->add('user', UserType::class);
            $builder->add('terms', CheckboxType::class, ['property_path' => 'termsAccepted']);
        }
    }

You don't need to use any special method to embed the ``UserType`` form.
A form is a field, too - you can add it like any other field, with the
expectation that the corresponding ``user`` property will hold an instance
of the class ``UserType``.

Handling the Form Submission
----------------------------

Next, you need a controller to handle the form. Start by creating a
controller that will display the registration form:

.. code-block:: php

    // src/Controller/AccountController.php
    namespace App\Controller;

    use App\Form\Model\Registration;
    use App\Form\Type\RegistrationType;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;

    class AccountController extends AbstractController
    {
        public function registerAction()
        {
            $form = $this->createForm(RegistrationType::class, new Registration());

            return $this->render('Account/register.html.twig', [
                'form' => $form->createView()
            ]);
        }
    }

and its template:

.. code-block:: html+jinja

    {# templates/Account/register.html.twig #}
    {{ form_start(form, {'action': path('create'), 'method': 'POST'}) }}
        {{ form_widget(form) }}

        <input type="submit" />
    {{ form_end(form) }}

Finally, create another action in ``AccountController``, which will handle
the form submission - perform its validation and save the User into MongoDB:

.. code-block:: php

    // src/Controller/AccountController.php
    public function createAction(DocumentManager $dm, Request $request)
    {
        $form = $this->createForm(RegistrationType::class, new Registration());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registration = $form->getData();

            $dm->persist($registration->getUser());
            $dm->flush();

            return $this->redirect(...);
        }

        return $this->render('Account/register.html.twig', [
            'form' => $form->createView()
        ]);
    }

That's it! Your form now validates sent data and allows you to save
the ``User`` object to MongoDB.

.. _`Security component`: https://symfony.com/doc/current/security.html
.. _`documentation`: https://symfony.com/doc/current/forms.html
