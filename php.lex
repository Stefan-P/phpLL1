@@lexical

lexical				/^@@lexical/
rules				/^@@rules/
nonSpace			/^[^ ]/
nonPHP				/^(.+)(\<\?|\z)/msiU
opManyOpen			/^\{/
opManyClose			/^\}/
opOptionOpen		/^\[/
opOptionClose		/^\]/
opParOpen			/^\(/
opParClose			/^\)/
opOneOrMany			/^\+/
opAlternate			/^\|/
opEnd				/^;/
opSQLiteral			/^(['].*['])/U
opDQLiteral			/^(["].*["])/U
opClass				/^class/
assign				/^=/
ident				/^([a-zA-Z][a-zA-Z0-9\_]*)/
phpVariable			/^(\$[a-zA-Z][a-zA-Z0-9\_]*)/
number				/^[0-9]+/
tokenClass			/^(@[a-zA-Z][a-zA-Z0-9]*)/
stringLiterals		/^('[^']*')/U
any					/^[\ ]+/
