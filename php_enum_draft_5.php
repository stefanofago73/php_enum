<?php declare(strict_types = 1);

namespace fago\lab
{
    abstract class  Enum
    {
      private string $name;
    
      private int $ordinal;
    
      /** @var array<string, array<string,Enum>> $enumsWorld */
      private static array $enumsWorld = [];
    
      /** @psalm-suppress PossiblyUnusedMethod  */
      protected final function __construct(String $name, int $ordinal)
      {
        $this->name=$name;
        $this->ordinal=$ordinal;
        $klazz = static::class;
        if(!isset(Enum::$enumsWorld[$klazz]))
        {
            Enum::$enumsWorld[$klazz]=[];
        }
        Enum::$enumsWorld[$klazz][$name]=$this;
      }
    
    public final function ordinal():int
    {
        return $this->ordinal;
    }
    
    public final function name():string
    {
        return $this->name;
    }
    
    
    // ...if you want to initialize Enum specific property you need to override this...
    protected function initializer():void
    {
    }
    
    public function __toString():string
    {
        return "Enum[".static::class." : ".$this->name."]";
    }
    
    /** @return array<int, string>  */
    public final function __sleep():array
    {
        throw new \LogicException("it's not expected to serialize an Enum...");
    }
    
    
    public final function __wakeup():void
    {
        throw new \LogicException("it's not expected to deserialize an Enum...");
    }
    
    
    public final function __clone()
    {
        throw new \LogicException("No clone allowed for Enum: ".static::class);
    }
    
    /** @return array<Enum> */
    public static final function values():array
    {
        $tmp =  Enum::$enumsWorld[static::class];
        return $tmp;
    }
    
    /**
     * @throws \UnexpectedValueException
     */
    public static final function valueOf(string $symbol):Enum
    {
        if( ($maybeFound = static::findSymbol($symbol))===null)
        {
            throw new \UnexpectedValueException("$symbol is not an ".static::class);
        }
        return $maybeFound;
    }
    
    /**
     *
     * If an Enum is related by name ith the input, then is returned
     * or a default is used. This is to trigger the definition of a
     * INVALID Enum in the Enumeration
     *
     */
    public static final function valueOrDefault(string $symbol,Enum $defaultValue):Enum
    {
        return static::findSymbol($symbol)??$defaultValue;
    }
    
    /**
     *
     * @param class-string<Enum> $fqcn
     * @throws \InvalidArgumentException
     *
     **/
    public static final function from($fqcn):void
    {
        
        self::initialValidation($fqcn);
        
        // Reflective element is only used to structural validation e initialization phase...
        $reflection = new \ReflectionClass($fqcn);
        
        self::structuralValidation($fqcn, $reflection);
        
        $staticProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_STATIC);
        
        foreach ($staticProperties as $index => $value)
        {
            $target = new $fqcn($value->getName(), intval($index,10));
            if(!is_a($target,Enum::class))
            {
                throw new \InvalidArgumentException("Not Enum Class passed for: ".$value->getName());
            }
            $target->initializer();
            $value->setValue($target);
        }
    }
    
    
    /**
     *
     * @param class-string<Enum> $fqcn
     * @throws \InvalidArgumentException ,
     *
     */
    private static function initialValidation(string $fqcn):void
    {
        if(isset(Enum::$enumsWorld[$fqcn]))
        {
            throw new \InvalidArgumentException("Enum $fqcn already defined!");
        }
    }
    
    
    
    /**
     *
     * @param \ReflectionClass<Enum> $reflection
     * @param class-string<Enum> $fqcn
     * @throws \InvalidArgumentException
     *
     */
    private static function structuralValidation(string $fqcn, \ReflectionClass $reflection):void
    {
        if(!$reflection->isFinal())
        {
            throw new \InvalidArgumentException("$fqcn need to be a final class");
        }
        
        $parent = $reflection->getParentClass();
        
        if( $parent === false || self::class !== $parent->getName() )
        {
            throw new \InvalidArgumentException("$fqcn need to be direct extension of Enum class");
        }
    }
    
    
    private static function findSymbol(string $symbol): ?Enum
    {
        foreach( (static::values()) as $elem)
        {
            if($elem->name() === $symbol)
            {
                return $elem;
            }
        }
        return null;
    }
  }
  
}



namespace enum\usage
{
  use fago\lab\Enum;
  
  final class CardType extends Enum
  {
    public static CardType $HEART;
    
    public static CardType $DIAMOND;
    
    public static CardType $CLUB;
    
    public static CardType $SPADE;
    
    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $description;
    
    // accessor function for the custom property
    public final function descript():string
    {
        return $this-> description;
    }
    
    // ...since a custom property is used, overriding the initialized method
    protected final function initializer():void{
        switch($this->name()){
            case 'HEART': $this->description = $this->heartDesc(); break;
            case 'DIAMON': $this->description = $this->diamonDesc(); break;
            case 'CLUB':   $this->description = $this->clubDesc(); break;
            case 'SPADE':  $this->description = $this->spadeDesc();break;
            default: break; // ...maybe throws exception...
        }
    }
    
    private final function heartDesc():string
    {
        return "My heart goes boom boom!";
    }
    
    private final function diamonDesc():string
    {
        return "I'm the preciuos one!";
    }
    
    private final function clubDesc():string
    {
        return "Flowers all around the world!";
    }
    
    private final function spadeDesc():string
    {
        return "Lemmy Docet!";
    }
    
  }

  
}


namespace
{
    use fago\lab\Enum;
    use enum\usage\CardType;
    
    
 Enum::from(CardType::class);

 printf("card type: %s description: %s".PHP_EOL
                  , CardType::$HEART->name()
				  , CardType::$HEART->descript());

 /** @var CardType $ctype */
 $ctype = CardType::valueOf('HEART');

 echo "they are the same: ".(CardType::$HEART === $ctype)."\n";
 
 
 function desk(CardType $card):void
 {
     switch($card)
     {
      case CardType::$HEART: echo "yeah heart\n"; break;
      case CardType::$DIAMOND: echo "yeah diamond\n"; break;
      case CardType::$CLUB: echo "yeah club\n"; break;
      case CardType::$SPADE: echo "yeah the ace of spade\n"; break;
     }
 }
 

 printf("card type: %s description: %s\n", $ctype->name(), $ctype->descript());

 printf("card type: %s\n", (String)$ctype);

 echo "card type: $ctype\n";


 desk($ctype);

 foreach( CardType::values() as $v)
 {
    echo $v.PHP_EOL;
 }
    
}

