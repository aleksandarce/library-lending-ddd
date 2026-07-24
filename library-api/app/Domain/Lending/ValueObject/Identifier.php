<?php

declare(strict_types = 1);

namespace App\Domain\Lending\ValueObject;

use InvalidArgumentException;

abstract class Identifier{
   final private function __construct(
     public readonly string $value,
 )
 {
 }

   public static function fromString(string $value): static{
     $value = trim($value);

     if($value === ''){
       throw new InvalidArgumentException(
         static::class . 'cannot be empty.'
       );
     }
     
     return new static($value);
   }

   public static function generate(): static{
     return new static(bin2hex(random_bytes(16)));
   }

   public function equals(self $other): bool{
     return $other::class === static::class && $other->value === $this->value;
   }

   public function __toString(): string
   {
     return $this->value;
   }
}