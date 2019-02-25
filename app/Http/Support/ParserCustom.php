<?php

namespace App\Http\Support;

use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\ParserException;

class ParserCustom extends Parser
{

    private $transliterations = [];

    public function addTransliteration($transliterations)
    {
        $this->transliterations[] = $transliterations;
    }

    /**
     * @param string $char
     */
    private function readTagName($char)
    {

        // remove special characters
        if (!empty($this->transliterations)) {
            $char    = mb_convert_encoding($char, "UTF-8");
            $char    = preg_replace(array_keys($this->transliterations), array_values($this->transliterations), $char);
        }

        if (preg_match('/^[a-zA-Z0-9_\+:\-\.\/]$/', $char)) {
            $this->appendToBuffer($char);
        } elseif ($this->isWhitespace($char) && empty($this->buffer)) {
            // Skips because we didn't start reading
        } elseif ('}' === $char && empty($this->buffer)) {
            // No tag name found, $char is just closing current entry
            $this->state = self::NONE;
        } else {
            $this->throwExceptionIfBufferIsEmpty($char);

            if (self::FIRST_TAG_NAME === $this->state) {
                // Takes a snapshot of current state to be triggered later as
                // tag name or citation key, see readPostTagName()
                $this->firstTagSnapshot = $this->takeBufferSnapshot();
            } else {
                // Current buffer is a simple tag name
                $this->triggerListenersWithCurrentBuffer();
            }

            // Once $char isn't a valid tag name character, it must be
            // interpreted as post tag name
            $this->state = self::POST_TAG_NAME;
            $this->readPostTagName($char);
        }
    }

    // /**
    //  * @param string $char
    //  */
    // private function readPostTagName($char)
    // {
    //     if ('=' === $char) {
    //         // First tag name isn't a citation key, because it has content
    //         $this->triggerListenersWithFirstTagSnapshotAs(self::TAG_NAME);
    //         $this->state = self::PRE_TAG_CONTENT;
    //     } elseif ('}' === $char) {
    //         // First tag name is a citation key, because $char closes entry and
    //         // lets first tag without value
    //         $this->triggerListenersWithFirstTagSnapshotAs(self::CITATION_KEY);
    //         $this->state = self::NONE;
    //     } elseif (',' === $char) {
    //         // First tag name is a citation key, because $char moves to the next
    //         // tag and lets first tag without value
    //         $this->triggerListenersWithFirstTagSnapshotAs(self::CITATION_KEY);
    //         $this->state = self::TAG_NAME;
    //     } elseif (!$this->isWhitespace($char)) {
    //         throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
    //     }
    // }

    // /**
    //  * @param string $char
    //  */
    // private function readPreTagContent($char)
    // {
    //     if (preg_match('/^[a-zA-Z0-9]$/', $char)) {
    //         // When concatenation is available it means there is already a
    //         // defined value, and parser expect a concatenator, a tag separator
    //         // or an entry closing char as next $char
    //         $this->throwExceptionAccordingToConcatenationAvailability($char, true);
    //         $this->state = self::RAW_TAG_CONTENT;
    //         $this->readRawTagContent($char);
    //     } elseif ('"' === $char) {
    //         // The exception is here for the same reason of the first case
    //         $this->throwExceptionAccordingToConcatenationAvailability($char, true);
    //         $this->tagContentDelimiter = '"';
    //         $this->state = self::QUOTED_TAG_CONTENT;
    //     } elseif ('{' === $char) {
    //         // The exception is here for the same reason of the first case
    //         $this->throwExceptionAccordingToConcatenationAvailability($char, true);
    //         $this->tagContentDelimiter = '}';
    //         $this->state = self::BRACED_TAG_CONTENT;
    //     } elseif ('#' === $char) {
    //         $this->throwExceptionAccordingToConcatenationAvailability($char, false);
    //         $this->mayConcatenateTagContent = false;
    //     } elseif (',' === $char) {
    //         $this->throwExceptionAccordingToConcatenationAvailability($char, false);
    //         $this->mayConcatenateTagContent = false;
    //         $this->state = self::TAG_NAME;
    //     } elseif ('}' === $char) {
    //         $this->throwExceptionAccordingToConcatenationAvailability($char, false);
    //         $this->mayConcatenateTagContent = false;
    //         $this->state = self::NONE;
    //     } elseif (!$this->isWhitespace($char)) {
    //         throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
    //     }
    // }

    // /**
    //  * @param string $char
    //  */
    // private function readRawTagContent($char)
    // {
    //     if (preg_match('/^[a-zA-Z0-9]$/', $char)) {
    //         $this->appendToBuffer($char);
    //     } else {
    //         $this->throwExceptionIfBufferIsEmpty($char);
    //         $this->triggerListenersWithCurrentBuffer();

    //         // once $char isn't a valid character
    //         // it must be interpreted as TAG_CONTENT
    //         $this->mayConcatenateTagContent = true;
    //         $this->state = self::PRE_TAG_CONTENT;
    //         $this->readPreTagContent($char);
    //     }
    // }

    // /**
    //  * @param string $char
    //  */
    // private function readDelimitedTagContent($char)
    // {
    //     if ($this->isTagContentEscaped) {
    //         $this->isTagContentEscaped = false;
    //         if ($this->tagContentDelimiter !== $char && '\\' !== $char && '%' !== $char) {
    //             $this->appendToBuffer('\\');
    //         }
    //         $this->appendToBuffer($char);
    //     } elseif ('}' === $this->tagContentDelimiter && '{' === $char) {
    //         ++$this->braceLevel;
    //         $this->appendToBuffer($char);
    //     } elseif ($this->tagContentDelimiter === $char) {
    //         if (0 === $this->braceLevel) {
    //             $this->triggerListenersWithCurrentBuffer();
    //             $this->mayConcatenateTagContent = true;
    //             $this->state = self::PRE_TAG_CONTENT;
    //         } else {
    //             --$this->braceLevel;
    //             $this->appendToBuffer($char);
    //         }
    //     } elseif ('\\' === $char) {
    //         $this->isTagContentEscaped = true;
    //     } else {
    //         $this->appendToBuffer($char);
    //     }
    // }

    // /**
    //  * @param string $char
    //  * @param string $previousState
    //  */
    // private function readOriginalEntry($char, $previousState)
    // {
    //     // Checks whether we are reading an entry character or not
    //     $isPreviousStateEntry = $this->isEntryState($previousState);
    //     $isCurrentStateEntry = $this->isEntryState($this->state);
    //     $isEntry = $isPreviousStateEntry || $isCurrentStateEntry;
    //     if (!$isEntry) {
    //         return;
    //     }

    //     // Appends $char to the original entry buffer
    //     if (empty($this->originalEntryBuffer)) {
    //         $this->originalEntryOffset = $this->offset;
    //     }
    //     $this->originalEntryBuffer .= $char;

    //     // Sends original entry to the listeners when $char closes an entry
    //     $isClosingEntry = $isPreviousStateEntry && !$isCurrentStateEntry;
    //     if ($isClosingEntry) {
    //         $this->triggerListeners($this->originalEntryBuffer, self::ENTRY, [
    //             'offset' => $this->originalEntryOffset,
    //             'length' => $this->offset - $this->originalEntryOffset + 1,
    //         ]);
    //         $this->originalEntryBuffer = '';
    //         $this->originalEntryOffset = null;
    //     }
    // }

    // // ----- Listener triggers -------------------------------------------------

    // /**
    //  * @param string $text
    //  * @param string $type
    //  * @param array  $context
    //  */
    // private function triggerListeners($text, $type, array $context)
    // {
    //     foreach ($this->listeners as $listener) {
    //         $listener->bibTexUnitFound($text, $type, $context);
    //     }
    // }

    // private function triggerListenersWithCurrentBuffer()
    // {
    //     $snapshot = $this->takeBufferSnapshot();
    //     $text = $snapshot['text'];
    //     $context = $snapshot['context'];
    //     $this->triggerListeners($text, $this->state, $context);
    // }

    // /**
    //  * @param string $type
    //  */
    // private function triggerListenersWithFirstTagSnapshotAs($type)
    // {
    //     if (empty($this->firstTagSnapshot)) {
    //         return;
    //     }
    //     $text = $this->firstTagSnapshot['text'];
    //     $context = $this->firstTagSnapshot['context'];
    //     $this->firstTagSnapshot = null;
    //     $this->triggerListeners($text, $type, $context);
    // }

    // // ----- Buffer tools ------------------------------------------------------

    // /**
    //  * @param string $char
    //  */
    // private function appendToBuffer($char)
    // {
    //     if (empty($this->buffer)) {
    //         $this->bufferOffset = $this->offset;
    //     }
    //     $this->buffer .= $char;
    // }

    // /**
    //  * @return array
    //  */
    // private function takeBufferSnapshot()
    // {
    //     $snapshot = [
    //         'text' => $this->buffer,
    //         'context' => [
    //             'offset' => $this->bufferOffset,
    //             'length' => $this->offset - $this->bufferOffset,
    //         ],
    //     ];
    //     $this->bufferOffset = null;
    //     $this->buffer = '';

    //     return $snapshot;
    // }

    // // ----- Exception throwers ------------------------------------------------

    // /**
    //  * @param string $char
    //  * @param bool   $availability
    //  */
    // private function throwExceptionAccordingToConcatenationAvailability($char, $availability)
    // {
    //     if ($availability === $this->mayConcatenateTagContent) {
    //         throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
    //     }
    // }

    // /**
    //  * @param string $char
    //  */
    // private function throwExceptionIfBufferIsEmpty($char)
    // {
    //     if (empty($this->buffer)) {
    //         throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
    //     }
    // }

    // /**
    //  * @param string $char
    //  */
    // private function throwExceptionIfReadingEntry($char)
    // {
    //     if ($this->isEntryState($this->state)) {
    //         throw ParserException::unexpectedCharacter($char, $this->line, $this->column);
    //     }
    // }

    // // ----- Auxiliaries -------------------------------------------------------

    // /**
    //  * @param string $state
    //  *
    //  * @return bool
    //  */
    // private function isEntryState($state)
    // {
    //     return self::NONE !== $state && self::COMMENT !== $state;
    // }

    // /**
    //  * @param string $char
    //  *
    //  * @return bool
    //  */
    // private function isWhitespace($char)
    // {
    //     return ' ' === $char || "\t" === $char || "\n" === $char || "\r" === $char;
    // }
}
