<?php

namespace spec\Documer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Documer\Storage\Memory;

class DocumerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith(new Memory());
        $this->shouldHaveType('Documer\Documer');
    }

    function it_should_be_trained()
    {
        $this->beConstructedWith(new Memory());

        $label = 'Politics';
        $words = 'Barack Obama is the first black president of the United States';

        $this->train($label, $words);

    }

    function it_should_guess_document_and_return_array()
    {
        $this->beConstructedWith(new Memory());

        $this->train('politics', 'This is text about Politics and more');
        $this->train('philosophy', 'Socrates is an ancient Greek philosopher');
        $this->train('athletic', 'Have no idea about athletics. Sorry.');
        $this->train('athletic', 'Not a clue.');
        $this->train('athletic', 'It is just not my thing.');

        $this->guess('What do we know about Socrates, the Greek philopher?')
             ->shouldBeArray();
    }

    function it_should_guess_document_for_label_and_return_boolean()
    {
        $this->beConstructedWith(new Memory());

        $this->train('politics', 'This is text about Politics and more');
        $this->train('philosophy', 'Socrates is an ancient Greek philosopher');
        $this->train('athletic', 'Have no idea about athletics. Sorry.');
        $this->train('athletic', 'Not a clue.');
        $this->train('athletic', 'It is just not my thing.');

        $this->is('philosophy', 'What do we know about Socrates, the Greek philopher?')
             ->shouldBeBool();
    }
}
