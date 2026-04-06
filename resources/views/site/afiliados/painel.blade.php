<html><body>
@if(isset($affiliate) && $affiliate->status === 'pendente')
  <p>análise</p>
@elseif(isset($linkIndicacao))
  <p>Link de Indicação</p>
@endif
</body></html>
