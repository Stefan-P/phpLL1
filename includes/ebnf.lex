@@lexical

lexical				/^@@lexical/
rules				/^@@rules/
opOneOrMany			/^\+/
opAlternate			/^\|/
opEnd				/^;/
opSQLiteral			/^(['].*['])/
opDQLiteral			/^(["].*["])/
opClass				/^class/
assign				/^=/
ident				/^[a-zA-Z][a-zA-Z0-9]*/
tokenClass			/^@[a-zA-Z][a-zA-Z0-9]*/
stringLiterals		/^(\'[^\']*\')|(\"[^\"]*\")/
any					/^[\ ]+/
