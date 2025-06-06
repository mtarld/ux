Symfony UX Turbo
================

Symfony UX Turbo is a Symfony bundle integrating the `Hotwire Turbo`_
library in Symfony applications. It is part of `the Symfony UX initiative`_.

Symfony UX Turbo allows having the same user experience as with
`Single Page Applications`_ but without having to write a single line of
JavaScript!

Symfony UX Turbo also integrates with `Symfony Mercure`_ or any other
transports to broadcast DOM changes to all currently connected users!

You're in a hurry? Take a look at :ref:`the chat example <chat-example>`
to discover the full potential of Symfony UX Turbo.

Or watch the `Turbo Screencast on SymfonyCasts`_.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-turbo

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

.. note::

    For more complex installation scenarios, you can install the JavaScript assets through the `@symfony/ux-turbo npm package`_

Usage
-----

Accelerating Navigation with Turbo Drive
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Turbo Drive enhances page-level navigation. It watches for link clicks
and form submissions, performs them in the background, and updates the
page without doing a full reload. This gives you the "single-page-app"
experience without major changes to your code!

Turbo Drive is automatically enabled when you install Symfony UX Turbo.
And while you don't need to make major changes to get things to work
smoothly, there are 3 things to be aware of:

1. Make sure your JavaScript is Turbo-ready
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Because navigation no longer results in full page refreshes, you may
need to adjust your JavaScript to work properly. The best solution is to
write your JavaScript using
`Stimulus`_ or something similar.

We also recommend that you place your ``script`` tags inside your
``head`` tag so that they aren't reloaded on every navigation (Turbo
re-executes any ``script`` tags inside ``body`` on every navigation).
Add a ``defer`` attribute to each ``script`` tag to prevent it from
blocking the page load. See `Moving <script> inside <head> and the "defer" Attribute`_
for more info.

2. Reloading When a JavaScript/CSS File Changes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Turbo drive can automatically perform a full refresh if the content of
one of your CSS or JS files *changes*, to ensure that your users always
have the latest version.

To enable this, first verify that you have versioning enabled in Encore
so that your filenames change when the file contents change:

.. code-block:: javascript

   // webpack.config.js

   Encore.
       // ...
       .enableVersioning(Encore.isProduction())

Then add a ``data-turbo-track="reload"`` attribute to all of your
``script`` and ``link`` tags:

.. code-block:: yaml

   # config/packages/webpack_encore.yaml
   webpack_encore:
       # ...

       script_attributes:
           defer: true
           'data-turbo-track': reload
       link_attributes:
           'data-turbo-track': reload

For more info, see: `Turbo Reloading When Assets Change`_.

3. Form Response Code Changes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Turbo Drive also converts form submissions to AJAX calls. To get it to
work, you *do* need to adjust your code to return a 422 status code on a
validation error (instead of a 200).

If you're using Symfony 6.2+, the ``render()`` method takes
care of this automatically::

    #[Route('/product/new', name: 'product_new')]
    public function newProduct(Request $request): Response
    {
        $form = $this->createForm(ProductFormType::class, null, [
            'action' => $this->generateUrl('product_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // save...

            return $this->redirectToRoute('product_list');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

If you're *not* using Symfony 6.2+, adjust your code
manually:

.. code-block:: diff

      #[Route('/product/new')]
      public function newProduct(Request $request): Response
      {
          $form = $this->createForm(ProductFormType::class);
          $form->handleRequest($request);

          if ($form->isSubmitted() && $form->isValid()) {
              // save...
          }

    +     $response = new Response(null, $form->isSubmitted() ? 422 : 200);

          return $this->render('product/new.html.twig', [
              'form' => $form->createView()
    -     ]);
    +     ], $response);
      }

This changes the response status code to 422 on validation error, which
tells Turbo Drive that the form submit failed and it should re-render
with the errors. You can *also* choose to change the success redirect
status code from 302 (the default) to 303 (``HTTP_SEE_OTHER``). That's
not required for Turbo Drive, but 303 is "more correct" for this
situation.

.. note::

    **NOTE:** When your form contains more than one submit button and,
    you want to check which of the buttons was clicked to adapt the
    program flow in your controller. You need to add a value to each
    button because Turbo Drive doesn't send element with empty value::

        $builder
            // ...
            ->add('save', SubmitType::class, [
                'label' => 'Create Task',
                'attr' => [
                    'value' => 'create-task'
                ]
            ])
            ->add('saveAndAdd', SubmitType::class, [
                'label' => 'Save and Add',
                'attr' => [
                    'value' => 'save-and-add'
                ]
            ]);

More Turbo Drive Info
^^^^^^^^^^^^^^^^^^^^^

`Read the Turbo Drive documentation`_ to learn
about the advanced features offered by Turbo Drive.

Decomposing Complex Pages with Turbo Frames
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Once Symfony UX Turbo is installed, you can also leverage `Turbo Frames`_:

.. code-block:: html+twig

    {# home.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <turbo-frame id="the_frame_id">
            <a href="{{ path('another-page') }}">This block is scoped, the rest of the page will not change if you click here!</a>
        </turbo-frame>
    {% endblock %}

.. code-block:: html+twig

    {# another-page.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <div>This will be discarded</div>

        <turbo-frame id="the_frame_id">
            The content of this block will replace the content of the Turbo Frame!
            The rest of the HTML generated by this template (outside of the Turbo Frame) will be ignored.
        </turbo-frame>
    {% endblock %}

The content of a frame can be lazy loaded:

.. code-block:: html+twig

    {# home.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <turbo-frame id="the_frame_id" src="{{ path('block') }}">
            A placeholder.
        </turbo-frame>
    {% endblock %}

In your controller, you can detect if the request has been triggered by
a Turbo Frame, and retrieve the ID of this frame::

    // src/Controller/MyController.php
    namespace App\Controller;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;

    class MyController
    {
        #[Route('/')]
        public function home(Request $request): Response
        {
            // Get the frame ID (will be null if the request hasn't been triggered by a Turbo Frame)
            $frameId = $request->headers->get('Turbo-Frame');

            // ...
        }
    }

<twig:Turbo:Frame> Twig Component
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. versionadded:: 2.22

    The ``<twig:Turbo:Frame>`` Twig Component was added in Turbo 2.22.

Simple example:

.. code-block:: html+twig

    <twig:Turbo:Frame id="the_frame_id" />

    {# renders as: #}
    <turbo-frame id="the_frame_id"></turbo-frame>

With a HTML attribute:

.. code-block:: html+twig

    <twig:Turbo:Frame id="the_frame_id" loading="lazy" src="{{ path('block') }}" />

    {# renders as: #}
    <turbo-frame id="the_frame_id" loading="lazy" src="https://example.com/block"></turbo-frame>

With content:

.. code-block:: html+twig

    <twig:Turbo:Frame id="the_frame_id" src="{{ path('block') }}">
        A placeholder.
    </twig:Turbo:Frame>

    {# renders as: #}
    <turbo-frame id="the_frame_id" src="https://example.com/block">
        A placeholder.
    </turbo-frame>

Writing Tests
^^^^^^^^^^^^^

Under the hood, Symfony UX Turbo relies on JavaScript to update the HTML
page. To test if your website works properly, you will have to write `UI tests`_.

Fortunately, we've got you covered! `Symfony Panther`_ is a convenient testing
tool using real browsers to test your Symfony application. It shares the
same API as BrowserKit, the functional testing tool shipped with
Symfony.

`Install Symfony Panther`_ and write a test for our Turbo Frame::

    // tests/TurboFrameTest.php
    namespace App\Tests;

    use Symfony\Component\Panther\PantherTestCase;

    class TurboFrameTest extends PantherTestCase
    {
        public function testFrame(): void
        {
            $client = self::createPantherClient();
            $client->request('GET', '/');

            $client->clickLink('This block is scoped, the rest of the page will not change if you click here!');
            $this->assertSelectorWillContain('body', 'This will replace the content of the Turbo Frame!');
        }
    }

Run ``bin/phpunit`` to execute the test! Symfony Panther automatically
starts your application with a web server and tests it using Google
Chrome or Firefox!

You can even watch changes happening in the browser by using:
``PANTHER_NO_HEADLESS=1 bin/phpunit --debug``

`Read the Turbo Frames documentation`_ to learn
everything you can do using Turbo Frames.

Coming Alive with Turbo Streams
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Turbo Streams are a way for the server to send partial page updates to
clients. There are two main ways to receive the updates:

-  in response to a user action, for instance when the user submits a
   form;
-  asynchronously, by sending updates to clients using
   `Mercure`_, `WebSocket`_ and similar protocols.

Forms
^^^^^

Let's discover how to use Turbo Streams to enhance your `Symfony forms`_::

    // src/Controller/TaskController.php
    namespace App\Controller;

    // ...
    use App\Entity\Task;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\UX\Turbo\TurboBundle;

    class TaskController extends AbstractController
    {
        public function new(Request $request): Response
        {
            $form = $this->createForm(TaskType::class, new Task());

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $task = $form->getData();
                // ... perform some action, such as saving the task to the database

                // 🔥 The magic happens here! 🔥
                if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                    // If the request comes from Turbo, set the content type as text/vnd.turbo-stream.html and only send the HTML to update
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                    return $this->renderBlock('task/new.html.twig', 'success_stream', ['task' => $task]);
                }

                // If the client doesn't support JavaScript, or isn't using Turbo, the form still works as usual.
                // Symfony UX Turbo is all about progressively enhancing your applications!
                return $this->redirectToRoute('task_success', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('task/new.html.twig', [
                'form' => $form,
            ]);
        }
    }

.. code-block:: html+twig

    {# bottom of new.html.twig #}
    {% block success_stream %}
        <turbo-stream action="replace" targets="#my_div_id">
            <template>
                The element having the id "my_div_id" will be replaced by this block, without a full page reload!

                <div>The task "{{ task }}" has been created!</div>
            </template>
        </turbo-stream>
    {% endblock %}

Supported actions are ``append``, ``prepend``, ``replace``, ``update``,
``remove``, ``before``, ``after`` and ``refresh``.
`Read the Turbo Streams documentation for more details`_.

Stream Messages and Actions
^^^^^^^^^^^^^^^^^^^^^^^^^^^

To render a ``<turbo-stream>`` element, this bundle provides a set of ``<twig:Turbo:Stream:*>`` Twig Components. These components make it easy to inject content directly into the ``<template>`` tag, pass attributes, and set the desired morphing mode with a clear and consistent syntax.

Append
""""""

.. code-block:: html+twig

    <twig:Turbo:Stream:Append target="#dom_id">
        Content to append to container designated with the dom_id.
    </twig:Turbo:Stream:Append>

    {# renders as: #}
    <turbo-stream action="append" targets="#dom_id">
        <template>
            Content to append to container designated with the dom_id.
        </template>
    </turbo-stream>

Prepend
"""""""

.. code-block:: html+twig

    <twig:Turbo:Stream:Prepend target="#dom_id">
        Content to prepend to container designated with the dom_id.
    </twig:Turbo:Stream:Prepend>

    {# renders as: #}
    <turbo-stream action="prepend" targets="#dom_id">
        <template>
            Content to prepend to container designated with the dom_id.
        </template>
    </turbo-stream>

Replace
"""""""

.. code-block:: html+twig

    <twig:Turbo:Stream:Replace target="#dom_id">
        Content to replace the element designated with the dom_id.
    </twig:Turbo:Stream:Replace>

    {# renders as: #}
    <turbo-stream action="replace" targets="#dom_id">
        <template>
            Content to replace the element designated with the dom_id.
        </template>
    </turbo-stream>

.. code-block:: html+twig

    {# with morphing #}
    <twig:Turbo:Stream:Replace target="#dom_id" morph>
        Content to replace the element.
    </twig:Turbo:Stream:Replace>

    {# renders as: #}
    <turbo-stream action="replace" targets="#dom_id" method="morph">
        <template>
            Content to replace the element.
        </template>
    </turbo-stream>

Update
""""""

.. code-block:: html+twig

    <twig:Turbo:Stream:Update target="#dom_id">
        Content to update to container designated with the dom_id.
    </twig:Turbo:Stream:Update>

    {# renders as: #}
    <turbo-stream action="update" targets="#dom_id">
        <template>
            Content to update to container designated with the dom_id.
        </template>
    </turbo-stream>

.. code-block:: html+twig

    {# with morphing #}
    <twig:Turbo:Stream:Update target="#dom_id" morph>
        Content to replace the element.
    </twig:Turbo:Stream:Update>

    {# renders as: #}
    <turbo-stream action="update" targets="#dom_id" method="morph">
        <template>
            Content to replace the element.
        </template>
    </turbo-stream>

Remove
""""""

.. code-block:: html+twig

    <twig:Turbo:Stream:Remove target="#dom_id" />

    {# renders as: #}
    <turbo-stream action="remove" targets="#dom_id"></turbo-stream>

Before
""""""

.. code-block:: html+twig

    <twig:Turbo:Stream:Before target="#dom_id">
        Content to place before the element designated with the dom_id.
    </twig:Turbo:Stream:Before>

    {# renders as: #}
    <turbo-stream action="before" targets="#dom_id">
        <template>
            Content to place before the element designated with the dom_id.
        </template>
    </turbo-stream>

After
"""""

.. code-block:: html+twig

    <twig:Turbo:Stream:After target="#dom_id">
        Content to place after the element designated with the dom_id.
    </twig:Turbo:Stream:After>

    {# renders as: #}
    <turbo-stream action="after" targets="#dom_id">
        <template>
            Content to place after the element designated with the dom_id.
        </template>
    </turbo-stream>

Refresh
"""""""

.. code-block:: html+twig

    {# without [request-id] #}
    <twig:Turbo:Stream:Refresh />

    {# renders as: #}
    <turbo-stream action="refresh"></turbo-stream>

.. code-block:: html+twig

    {# debounced with [request-id] #}
    <twig:Turbo:Stream:Refresh requestId="abcd-1234" />

    {# renders as: #}
    <turbo-stream action="refresh" request-id="abcd-1234"></turbo-stream>

Resetting the Form
~~~~~~~~~~~~~~~~~~

When you return a Turbo stream, *only* the elements in that stream template will
be updated. This means that if you want to reset the form, you need to include
a new form in the stream template.

To do that, first isolate your form rendering into a block so you can reuse it:

.. code-block:: diff

    {# new.html.twig #}
    +{% block task_form %}
     {{ form(form) }}
    +{% endblock %}

Now, create a "fresh" form and pass it into your stream:

.. code-block:: diff

    // src/Controller/TaskController.php
    // ...

    class TaskController extends AbstractController
    {
        public function new(Request $request): Response
        {
            $form = $this->createForm(TaskType::class, new Task());

   +        $emptyForm = clone $form;
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // ...

                if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                    return $this->renderBlock('task/new.html.twig', 'success_stream', [
                        'task' => $task,
   +                    'form' => $emptyForm,
                    ]);
                }

                // ...
                return $this->redirectToRoute('task_success', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('task/new.html.twig', [
                'form' => $form,
            ]);
        }
    }

Now, in your stream template, "replace" the entire form:

.. code-block:: diff

    {# new.html.twig #}
     {% block success_stream %}
    +<turbo-stream action="replace" targets="form[name=task]">
    +    <template>
    +        {{ block('task_form') }}
    +    </template>
    +</turbo-stream>
     <turbo-stream action="replace" targets="#my_div_id">

.. _chat-example:

Sending Async Changes using Mercure: a Chat
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Symfony UX Turbo also supports broadcasting HTML updates to all
currently connected clients, using the
`Mercure`_ protocol or any other.

To illustrate this, let's build a chat system with **0 lines of
JavaScript**!

Start by installing `the Mercure support`_ on your project:

.. code-block:: terminal

    $ composer require symfony/mercure-bundle

Then, enable the "mercure stream" controller in ``assets/controllers.json``:

.. code-block:: diff

    "@symfony/ux-turbo": {
        "mercure-turbo-stream": {
    +         "enabled": true,
    -         "enabled": false,
            "fetch": "eager"
        }
    },

The easiest way to have a working development (and production-ready)
environment is to use `Symfony Docker`_, which comes with
a Mercure hub integrated in the web server.

If you use Symfony Flex, the configuration has been generated for you,
be sure to update the ``MERCURE_URL`` in the ``.env`` file to point to a
Mercure Hub (it's not necessary if you are using Symfony Docker).

Otherwise, configure Mercure Hub(s) as explained in the documentation:

.. code-block:: yaml

    # config/packages/mercure.yaml
    mercure:
        hubs:
            default:
                url: '%env(MERCURE_URL)%'
                public_url: '%env(MERCURE_PUBLIC_URL)%'
                jwt:
                    secret: '%env(MERCURE_JWT_SECRET)%'
                    publish: '*'

Let's create our chat::

    // src/Controller/ChatController.php
    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Mercure\HubInterface;
    use Symfony\Component\Mercure\Update;

    class ChatController extends AbstractController
    {
        public function chat(Request $request, HubInterface $hub): Response
        {
            $form = $this->createFormBuilder()
                ->add('message', TextType::class, ['attr' => ['autocomplete' => 'off']])
                ->add('send', SubmitType::class)
                ->getForm();

            $emptyForm = clone $form; // Used to display an empty form after a POST request
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                // 🔥 The magic happens here! 🔥
                // The HTML update is pushed to the client using Mercure
                $hub->publish(new Update(
                    'chat',
                    $this->renderView('chat/message.stream.html.twig', ['message' => $data['message']])
                ));

                // Force an empty form to be rendered below
                // It will replace the content of the Turbo Frame after a post
                $form = $emptyForm;
            }

            return $this->render('chat/index.html.twig', [
                'form' => $form,
             ]);
        }
    }

.. code-block:: html+twig

    {# chat/index.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <h1>Chat</h1>

        <div id="messages" {{ turbo_stream_listen('chat') }}>
            {#
                The messages will be displayed here.
                "turbo_stream_listen()" automatically registers a Stimulus controller that subscribes to the "chat" topic as managed by the transport.
                All connected users will receive the new messages!
             #}
        </div>

        <turbo-frame id="message_form">
            {{ form(form) }}

            {#
                The form is displayed in a Turbo Frame, with this trick a new empty form is displayed after every post,
                but the rest of the page will not change.
            #}
        </turbo-frame>
    {% endblock %}

If you're using a private hub, you can add ``{ withCredentials: true }``
as ``turbo_stream_listen()`` third argument to authenticate with the hub

.. code-block:: html+twig

    {# chat/message.stream.html.twig #}
    {# New messages received through the Mercure connection are appended to the div with the "messages" ID. #}
    <turbo-stream action="append" targets="#messages">
        <template>
            <div>{{ message }}</div>
        </template>
    </turbo-stream>

Keep in mind that you can use all features provided by Symfony Mercure,
including `private updates`_ (to ensure that only authorized users will
receive the updates) and `async dispatching with Symfony Messenger`_.

Broadcast Doctrine Entities Update
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Symfony UX Turbo also comes with a convenient integration with Doctrine
ORM.

With a single attribute, your clients can subscribe to creations,
updates and deletions of entities::

    // src/Entity/Book.php
    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\UX\Turbo\Attribute\Broadcast;

    #[ORM\Entity]
    #[Broadcast] // 🔥 The magic happens here
    class Book
    {
        #[ORM\Column, ORM\Id, ORM\GeneratedValue(strategy: "AUTO")]
        public ?int $id = null;

        #[ORM\Column]
        public string $title = '';
    }

To subscribe to updates of an entity, pass it as parameter of the
``turbo_stream_listen()`` Twig helper:

.. code-block:: html+twig

    <div id="book_{{ book.id }}" {{ turbo_stream_listen(book) }}></div>

Alternatively, you can subscribe to updates made to all entities of a
given class by using its Fully Qualified Class Name:

.. code-block:: html+twig

    <div id="books" {{ turbo_stream_listen('App\\Entity\\Book') }}></div>

Finally, create the template that will be rendered when an entity is
created, modified or deleted:

.. code-block:: html+twig

    {# templates/broadcast/Book.stream.html.twig #}
    {% block create %}
        <turbo-stream action="append" targets="#books">
            <template>
                <div id="{{ 'book_' ~ id }}">{{ entity.title }} (#{{ id }})</div>
            </template>
        </turbo-stream>
    {% endblock %}

    {% block update %}
        <turbo-stream action="update" targets="#book_{{ id }}">
            <template>
                {{ entity.title }} (#{{ id }}, updated)
            </template>
        </turbo-stream>
    {% endblock %}

    {% block remove %}
        <turbo-stream action="remove" targets="#book_{{ id }}"></turbo-stream>
    {% endblock %}

By convention, Symfony UX Turbo will look for a template named
``templates/broadcast/{ClassName}.stream.html.twig``. This template
**must** contain at least 3 blocks: ``create``, ``update`` and
``remove`` (they can be empty, but they must exist).

Every time an entity marked with the ``Broadcast`` attribute changes,
Symfony UX Turbo will render the associated template and will broadcast
the changes to all connected clients.

Each block must contain a list of Turbo Stream actions. These actions
will be automatically applied by Turbo to the DOM tree of every
connected client. Each template can contain as many actions as needed.

For instance, if the same entity is displayed on different pages, you
can include all actions updating these different places in the template.
Actions applying to non-existing DOM elements will simply be ignored.

The current entity, the string representation of its identifier(s), the
action (``create``, ``update`` or ``remove``) and options set on the
``Broadcast`` attribute are passed to the template as variables:
``entity``, ``id``, ``action`` and ``options``.

Broadcast Conventions and Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Because Symfony UX Turbo needs access to their identifier, entities have
to either be managed by Doctrine ORM, have a public property named
``id``, or have a public method named ``getId()``.

Symfony UX Turbo will look for a template named after mapping their
Fully Qualified Class Names. For example and by default, if a class
marked with the ``Broadcast`` attribute is named ``App\Entity\Foo``, the
corresponding template will be found in
``templates/broadcast/Foo.stream.html.twig``.

It's possible to configure own namespaces are mapped to templates by
using the ``turbo.broadcast.entity_template_prefixes`` configuration
options. The default is defined as such:

.. code-block:: yaml

    # config/packages/turbo.yaml
    turbo:
        broadcast:
            entity_template_prefixes:
                App\Entity\: broadcast/

Finally, it's also possible to explicitly set the template to use with
the ``template`` parameter of the ``Broadcast`` attribute::

    #[Broadcast(template: 'my-template.stream.html.twig')]
    class Book { /* ... */ }

Broadcast Options
~~~~~~~~~~~~~~~~~

The ``Broadcast`` attribute comes with a set of handy options:

-  ``transports`` (``string[]``): a list of transports to broadcast to
-  ``topics`` (``string[]``): a list of topics to use, the default topic
   is derived from the FQCN of the entity and from its id
-  ``template`` (``string``): Twig template to render (see above)

The ``Broadcast`` attribute can be repeated (e.g. you can have multiple
``#[Broadcast]``. This is convenient to render several templates associated with
their own topics for the same change (e.g. the same data is rendered in different
way in the list and in the detail pages).

Options are transport-specific. When using Mercure, some extra options
are supported:

-  ``private`` (``bool``): marks Mercure updates as private
-  ``sse_id`` (``string``): ``id`` field of the SSE
-  ``sse_type`` (``string``): ``type`` field of the SSE
-  ``sse_retry`` (``int``): ``retry`` field of the SSE

The Mercure broadcaster also supports `Expression Language`_ in topics
by starting with ``@=``.

Example::

    // src/Entity/Book.php
    namespace App\Entity;

    use Symfony\UX\Turbo\Attribute\Broadcast;

    #[Broadcast(topics: ['@="book_detail" ~ entity.getId()', 'books'], template: 'book_detail.stream.html.twig', private: true)]
    #[Broadcast(topics: ['@="book_list" ~ entity.getId()', 'books'], template: 'book_list.stream.html.twig', private: true)]
    class Book
    {
        // ...
    }

Using Multiple Transports
~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX Turbo allows sending Turbo Streams updates using multiple
transports. For instance, it's possible to use several Mercure hubs with
the following configuration:

.. code-block:: yaml

    # config/packages/mercure.yaml
    mercure:
        hubs:
            hub1:
                url: https://hub1.example.net/.well-known/mercure
                jwt: snip
            hub2:
                url: https://hub2.example.net/.well-known/mercure
                jwt: snip

Use the appropriate Mercure ``HubInterface`` service to send a change
using a specific transport::

    // src/Controller/MyController.php
    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Mercure\HubInterface;
    use Symfony\Component\Mercure\Update;

    class MyController extends AbstractController
    {
        public function publish(HubInterface $hub1): Response
        {
            $id = $hub1->publish(new Update('topic', 'content'));

            return new Response("Update #{$id} published.");
        }
    }

Changes made to entities marked with the ``#[Broadcast]`` attribute will
be sent using all configured transport by default. You can specify the
list of transports to use for a specific entity class using the
``transports`` parameter::

    // src/Entity/Book.php
    namespace App\Entity;

    use Symfony\UX\Turbo\Attribute\Broadcast;

    #[Broadcast(transports: ['hub1', 'hub2'])]
    /** ... */
    class Book
    {
        // ...
    }

Finally, generate the HTML attributes registering the Stimulus
controller corresponding to your transport by passing an extra argument
to ``turbo_stream_listen()``:

.. code-block:: html+twig

    <div id="messages" {{ turbo_stream_listen('App\\Entity\\Book', 'hub2') }}></div>

Registering a Custom Transport
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you prefer using another protocol than Mercure, you can create custom
transports::

    // src/Turbo/Broadcaster.php
    namespace App\Turbo;

    use Symfony\UX\Turbo\Attribute\Broadcast;
    use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

    class Broadcaster implements BroadcasterInterface
    {
        public function broadcast(object $entity, string $action): void
        {
            // This method will be called every time an object marked with the #[Broadcast] attribute is changed
            $attribute = (new \ReflectionClass($entity))->getAttributes(Broadcast::class)[0] ?? null;
            // ...
        }
    }

Then a stream listener::

    // src/Turbo/TurboStreamListenRenderer.php
    namespace App\Turbo;

    use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
    use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
    use Symfony\UX\Turbo\Twig\TurboStreamListenRendererInterface;
    use Twig\Environment;

    #[AsTaggedItem(index: 'my-transport')]
    class TurboStreamListenRenderer implements TurboStreamListenRendererInterface
    {
        public function __construct(
            private StimulusHelper $stimulusHelper,
        ) {}

        public function renderTurboStreamListen(Environment $env, $topic): string
        {
            $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
            $stimulusAttributes->addController('your_stimulus_controller', [
                /* controller values such as topic */
            ]);

            return (string) $stimulusAttributes;
        }
    }

The broadcaster must be registered as a service tagged with
``turbo.broadcaster`` and the renderer must be tagged with
``turbo.renderer.stream_listen``. If you enabled `autoconfigure option`_
(it's the case by default), these tags will be added automatically
because these classes implement the ``BroadcasterInterface`` and
``TurboStreamListenRendererInterface`` interfaces, the related services
will be.

Meta Tags
~~~~~~~~~

turbo_exempts_page_from_cache
.............................

.. code-block:: twig

    {{ turbo_exempts_page_from_cache() }}

Generates a <meta> tag to disable caching of a page.

turbo_exempts_page_from_preview
...............................

.. code-block:: twig

    {{ turbo_exempts_page_from_preview() }}

Generates a <meta> tag to specify cached version of the page should not be shown as a preview on regular navigation visits.

turbo_page_requires_reload
..........................

.. code-block:: twig

    {{ turbo_page_requires_reload() }}

Generates a <meta> tag to force a full page reload.

turbo_refreshes_with
....................

.. code-block:: twig

    {{ turbo_refreshes_with(method: 'replace', scroll: 'reset') }}

``method`` *(optional)*
    **type**: ``string`` **default**: ``replace`` **allowed values**: ``replace`` or ``morph``
``scroll`` *(optional)*
    **type**: ``string`` **default**: ``reset`` **allowed values**: ``reset`` or ``preserve``

Generates <meta> tags to configure both the refresh method and scroll behavior for page refreshes.

turbo_refresh_method
....................

.. code-block:: twig

    {{ turbo_refresh_method(method: 'replace') }}

``method`` *(optional)*
    **type**: ``string`` **default**: ``replace`` **allowed values**: ``replace`` or ``morph``

Generates a <meta> tag to configure the refresh method for page refreshes.

turbo_refresh_scroll
....................

.. code-block:: twig

    {{ turbo_refresh_scroll(scroll: 'reset') }}

``scroll`` *(optional)*
    **type**: ``string`` **default**: ``reset`` **allowed values**: ``reset`` or ``preserve``

Generates a <meta> tag to configure the scroll behavior for page refreshes.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

Credits
-------

Symfony UX Turbo has been created by `Kévin Dunglas`_. It has been inspired by
`hotwired/turbo-rails`_ and `sroze/live-twig`_.

.. _`Hotwire Turbo`: https://turbo.hotwired.dev
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Single Page Applications`: https://en.wikipedia.org/wiki/Single-page_application
.. _`Symfony Mercure`: https://symfony.com/doc/current/mercure.html
.. _`Turbo Screencast on SymfonyCasts`: https://symfonycasts.com/screencast/turbo
.. _`Stimulus`: https://stimulus.hotwired.dev
.. _`Turbo Reloading When Assets Change`: https://turbo.hotwired.dev/handbook/drive#reloading-when-assets-change
.. _`Read the Turbo Drive documentation`: https://turbo.hotwired.dev/handbook/drive
.. _`Turbo Frames`: https://turbo.hotwired.dev/handbook/introduction#turbo-frames-decompose-complex-pages
.. _`Read the Turbo Frames documentation`: https://turbo.hotwired.dev/handbook/introduction#turbo-frames-decompose-complex-pages
.. _`UI tests`: https://martinfowler.com/articles/practical-test-pyramid.html#UiTests
.. _`Symfony Panther`: https://github.com/symfony/panther
.. _`Install Symfony Panther`: https://github.com/symfony/panther#installing-panther
.. _`Mercure`: https://mercure.rocks
.. _`WebSocket`: https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API
.. _`Symfony forms`: https://symfony.com/doc/current/forms.html
.. _`Read the Turbo Streams documentation for more details`: https://turbo.hotwired.dev/handbook/streams
.. _`the Mercure support`: https://symfony.com/doc/current/mercure.html
.. _`Symfony Docker`: https://github.com/dunglas/symfony-docker
.. _`autoconfigure option`: https://symfony.com/doc/current/service_container.html#the-autoconfigure-option
.. _`private updates`: https://symfony.com/doc/current/mercure.html#authorization
.. _`async dispatching with Symfony Messenger`: https://symfony.com/doc/current/mercure.html#async-dispatching
.. _`Kévin Dunglas`: https://dunglas.fr
.. _`hotwired/turbo-rails`: https://github.com/hotwired/turbo-rails
.. _`sroze/live-twig`: https://github.com/sroze/live-twig
.. _`Moving <script> inside <head> and the "defer" Attribute`: https://symfony.com/blog/moving-script-inside-head-and-the-defer-attribute
.. _`Expression Language`: https://symfony.com/doc/current/components/expression_language.html
.. _`@symfony/ux-turbo npm package`: https://www.npmjs.com/package/@symfony/ux-turbo
