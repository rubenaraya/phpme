<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="utf-8" indent="yes" />
		<xsl:param name="vista" />

	<xsl:template name="funciones_instructor">
		<script type="text/javascript">
		//<![CDATA[
		function ME_Instructor() {};
		ME_Instructor.prototype.validarItem = function( id, marcar ) {
			var campo = id.replace( 'item_', '' );
			var marcados = 0;
			jQuery('#' + id + " input").each(function() {
				var tipo = jQuery(this).prop('type');
				switch (tipo) {
				case 'radio':
				case 'checkbox':
					if ( jQuery(this).prop('checked') == true ) { marcados++; }
					break;
				case 'text':
				case 'number':
					var valor = jQuery(this).val();
					if ( valor.length > 0 ) { marcados++; }
					break;
				}
			});
			jQuery('#' + id + " textarea").each(function() {
				var valor = jQuery(this).val();
				if ( valor.length > 0 ) { marcados++; }
			});
			if (marcar) {
				jQuery( '#' + id ).removeClass( 'me-suave' );
				if ( marcados > 0 ) {
					jQuery( '#' + id ).removeClass( 'me-rojo' );
					jQuery( '#' + id ).addClass( 'me-verde' );
				} else {
					jQuery( '#' + id ).removeClass( 'me-verde' );
					jQuery( '#' + id ).addClass( 'me-rojo' );
				}
			}
			if ( marcados > 0 ) {
				return 1;
			} else {
				return 0;
			}
		};
		ME_Instructor.prototype.escribirTexto = function( id, area ) {
			var valor = jQuery( '#' + id ).val();
			var tipo = '';
			var clase = 'me-suave';
			if ( jQuery( '#' + area ).hasClass( 'me-requerido' ) ) {
				clase = 'me-rojo';
			}
			if ( jQuery( 'textarea#' + id ).length >0 ) { tipo = 'TEXTAREA'; } 
			else if ( jQuery( 'input#' + id ).length >0 ) { tipo = 'INPUT'; }
			jQuery( 'div#' + area ).removeClass( 'me-suave' );
			if ( tipo == 'INPUT' ) {
				if ( valor == '' || valor == null || typeof valor==="undefined" ) {
					jQuery( 'div#' + area ).removeClass( 'me-verde' );
					jQuery( 'div#' + area ).addClass( clase );
				} else {
					jQuery( 'div#' + area ).removeClass( clase );
					jQuery( 'div#' + area ).addClass( 'me-verde' );
				}
			} else if ( tipo == 'TEXTAREA' ) {
				if ( valor == '' || valor == null || typeof valor==="undefined" ) {
					jQuery( 'div#' + area ).removeClass( 'me-verde' );
					jQuery( 'div#' + area ).addClass( clase );
				} else {
					jQuery( 'div#' + area ).removeClass( clase );
					jQuery( 'div#' + area ).addClass( 'me-verde' );
				}
			}
			M.Instructor.contarItems();
		};
		ME_Instructor.prototype.elegirUno = function( id, area, campo ) {
			if ( campo == null || typeof campo==="undefined" ) { campo = ''; }
			var tipo = '';
			if ( jQuery( 'select#' + id ).length >0 ) { tipo = 'SELECT'; } 
			else if ( jQuery( 'input#' + id ).length >0 ) { tipo = 'RADIO'; }
			if ( tipo == 'SELECT' ) {
				var valor = jQuery( 'select#' + id ).val();
				jQuery( 'div#' + area ).removeClass( 'me-suave' );
				if ( valor == '' || valor == null || typeof valor==="undefined" ) {
					jQuery( 'div#' + area ).removeClass( 'me-verde' );
					jQuery( 'div#' + area ).addClass( 'me-rojo' );
				} else {
					jQuery( 'div#' + area ).removeClass( 'me-rojo' );
					jQuery( 'div#' + area ).addClass( 'me-verde' );
					if ( campo.length > 0 ) {
						jQuery( '#' + campo ).removeClass( 'me-ocultar' ); 
						jQuery( '#' + campo ).show();
						jQuery( '#' + campo ).focus();
					}
				}
			} else if ( tipo == 'RADIO' ) {
				jQuery( 'div#' + area ).removeClass( 'me-suave' );
				if ( jQuery( 'input#' + id ).prop('checked') == true ) {
					jQuery( 'div#' + area ).removeClass( 'me-rojo' );
					jQuery( 'div#' + area ).addClass( 'me-verde' );
					if ( campo.length > 0 ) {
						jQuery( '#' + campo ).removeClass( 'me-ocultar' ); 
						jQuery( '#' + campo ).show();
						jQuery( '#' + campo ).focus();
					}
				} else {
					jQuery( 'div#' + area ).removeClass( 'me-verde' ); 
					jQuery( 'div#' + area ).addClass( 'me-rojo' ); 
				}
			}
			M.Instructor.contarItems();
		};
		ME_Instructor.prototype.elegirVarios = function( clase, area ) {
			var num = 0;
			jQuery( 'input.' + clase ).each( function() { 
				if ( this.checked==true ) { num = num + 1; }
			});
			if ( num > 0 ) {
				jQuery( 'div#' + area ).removeClass( 'me-suave' );
				jQuery( 'div#' + area ).addClass( 'me-verde' );
			} else {
				jQuery( 'div#' + area ).removeClass( 'me-verde' ); 
				jQuery( 'div#' + area ).addClass( 'me-suave' ); 
			}
			M.Instructor.contarItems();
		};
		ME_Instructor.prototype.vincularCampo = function( campo ) {
			if ( campo == null || typeof campo==="undefined" ) { campo = ''; }
			if ( jQuery( 'input#' + campo ).length >0 ) {
				jQuery( '#' + campo ).prop('checked', true);
			}
		};
		ME_Instructor.prototype.contarItems = function() {
			var total = 0;
			var respondido = 0;
			jQuery('div.me-item').each(function() {
				total++;
				respondido += M.Instructor.validarItem( jQuery(this).prop('id'), false );
			});
			jQuery('#M_TOTAL').html(total.toString());
			jQuery('#M_RESPONDIDO').html(respondido.toString());
			return respondido;
		};
		M.Instructor = new ME_Instructor();
		//]]>
		</script>
	</xsl:template>

	<xsl:template name="campo_web">
		<xsl:param name="item" />
		<xsl:variable name="id" select="$item/@id" />
		<xsl:choose>
			<xsl:when test="$item/@forma='alternativas_elegir'">
				<p><xsl:value-of select="$item/@enunciado" /></p>
				<xsl:for-each select="$item/opcion">
					<xsl:variable name="pos" select="position()" />
					<div class="rba">
						<input type="radio" value="{@cod}" name="{$id}" id="{$id}_{$pos}" onclick="M.Cuestionario.elegirUno('{$id}_{$pos}', 'item_{$id}', '{@vincular}')"/>
						<label for="{$id}_{$pos}">&#160;<xsl:value-of select="@etiqueta" />&#160;
							<xsl:if test="string-length(@vincular)>0">
								<input type="text" name="{@vincular}" id="{@vincular}" class="form-control me-ocultar" onfocus="M.Cuestionario.vincularCampo('{$id}_{$pos}')" placeholder="((¿Cual?))" />
							</xsl:if>
						</label>
					</div>
				</xsl:for-each>
			</xsl:when>
			<xsl:when test="substring($item/@forma,1,7)='escala_'">
				<p><xsl:value-of select="$item/@enunciado" /></p>
				<xsl:for-each select="//cuestionario/escala[@id=$item/@forma]/opcion">
					<div class="rba">
						<input type="radio" value="{@cod}" name="{$id}" id="{$id}_{@cod}" onclick="M.Cuestionario.elegirUno('{$id}_{@cod}', 'item_{$id}', '')" />
						<label for="{$id}_{@cod}">&#160;<xsl:value-of select="@etiqueta" />&#160;</label>
					</div>
				</xsl:for-each>
			</xsl:when>
			<xsl:when test="$item/@forma='elemento_chequeable'">
				<div class="rbo">
					<input type="checkbox" value="1" name="{$id}" id="{$id}" onclick="M.Cuestionario.elegirUno('{$id}', 'item_{$id}', '')" />
					<label for="{$id}">&#160;<xsl:value-of select="$item/@enunciado" />&#160;</label>
				</div>
			</xsl:when>
			<xsl:when test="$item/@forma='numero_linea'">
				<p><xsl:value-of select="$item/@enunciado" /></p>
				<div class="form-group">
					<input type="number" name="{$id}" id="{$id}" class="form-control" min="{$item/@min}" max="{$item/@max}" onblur="M.Cuestionario.escribirTexto('{$id}', 'item_{$id}')" />
				</div>
			</xsl:when>
			<xsl:when test="$item/@forma='texto_linea'">
				<p><xsl:value-of select="$item/@enunciado" /></p>
				<div class="form-group">
					<input type="text" name="{$id}" id="{$id}" class="form-control"  onblur="M.Cuestionario.escribirTexto('{$id}', 'item_{$id}')" />
				</div>
			</xsl:when>
			<xsl:when test="$item/@forma='texto_area'">
				<p><xsl:value-of select="$item/@enunciado" /></p>
				<div class="form-group">
					<textarea name="{$id}" id="{$id}" class="form-control" rows="5" style="height: 125px; max-width: 100%" onblur="M.Cuestionario.escribirTexto('{$id}', 'item_{$id}')"></textarea>
				</div>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="campo_pdf">
		<xsl:param name="item" />
		<xsl:choose>
			<xsl:when test="$item/@forma='alternativas_elegir'">
				<div><xsl:value-of select="$item/@enunciado" /></div>
				<xsl:for-each select="$item/opcion">
					<div><span class="cuadro">&#160;&#160;&#160;&#160;&#160;</span>&#160;<xsl:value-of select="@etiqueta" />
					<xsl:if test="string-length(@vincular)>0">&#160;&#160;______________________________</xsl:if>
					</div>
				</xsl:for-each>
				<br/>
			</xsl:when>
			<xsl:when test="substring($item/@forma,1,7)='escala_'">
				<div style="margin-top: 0.5em; margin-bottom: 0.25em;">
					<div><xsl:value-of select="$item/@enunciado" /></div>
					<div style="font-size: 0.9em">
						<xsl:for-each select="//cuestionario/escala[@id=$item/@forma]/opcion">
							<span class="cuadro">&#160;&#160;&#160;&#160;&#160;</span>&#160;<xsl:value-of select="@etiqueta" />&#160;&#160;
						</xsl:for-each>
					</div>
				</div>
			</xsl:when>
			<xsl:when test="$item/@forma='elemento_chequeable'">
				<div><span class="cuadro">&#160;&#160;&#160;&#160;&#160;</span>&#160;<xsl:value-of select="$item/@enunciado" /></div>
			</xsl:when>
			<xsl:when test="$item/@forma='numero_linea'">
				<div><xsl:value-of select="$item/@enunciado" />&#160;&#160;_______________</div>
				<br/>
			</xsl:when>
			<xsl:when test="$item/@forma='texto_linea'">
				<div><xsl:value-of select="$item/@enunciado" /></div>
				<div class="cuadro"><br/><br/></div>
			</xsl:when>
			<xsl:when test="$item/@forma='texto_area'">
				<div class="txt_grupo"><br/><xsl:value-of select="$item/@enunciado" /></div>
				<div class="cuadro"><br/><br/><br/><br/><br/></div>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="tablas_cruces">
		<xsl:param name="variable" />
		<xsl:param name="cruce" />
		<xsl:param name="seleccion" />
		<xsl:variable name="datos" select="//resultados[@grupo='recuentos']/elemento[item=$variable]" />
		<xsl:variable name="columnas" select="//categoria[@id=$cruce]" />
		<xsl:variable name="visibles">
			<xsl:choose>
				<xsl:when test="string-length($seleccion)>0">
					<xsl:value-of select="$seleccion" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:for-each select="//categoria[@id=$cruce]/segmento">
						<xsl:value-of select="@valor" />
						<xsl:if test="position() != last()">,</xsl:if>
					</xsl:for-each>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:if test="count($datos)>0">
			<xsl:variable name="nombre">
				<xsl:choose>
					<xsl:when test="count(//categoria[@id=$variable])>0">
						<xsl:value-of select="//categoria[@id=$variable]/@etiqueta" />
					</xsl:when>
					<xsl:when test="count(//item[@id=$variable])>0">
						<xsl:value-of select="//item[@id=$variable]/@recuento" />
					</xsl:when>
					<xsl:when test="$variable=$campo_grupo">
						<xsl:value-of select="$nombre_grupo" />
					</xsl:when>
					<xsl:otherwise><xsl:value-of select="$variable" /></xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<table class="informes">
				<tr>
					<th rowspan="2">
						<xsl:value-of select="$nombre" />
					</th>
					<th rowspan="2" colspan="2">((Subtotales))</th>
					<th colspan="{count($columnas/segmento[contains( concat(',',$visibles,','), concat(',',@valor,',') )]) * 2}" class="categoria"><xsl:value-of select="$columnas/@etiqueta" /></th>
				</tr>
				<tr>
					<xsl:for-each select="$columnas/segmento[contains( concat(',',$visibles,','), concat(',',@valor,',') )]">
						<td colspan="2" class="segmento"><xsl:value-of select="@etiqueta" /></td>
					</xsl:for-each>
				</tr>
				<xsl:for-each select="$datos">
					<xsl:variable name="fila" select="." />
					<xsl:variable name="opcion" select="opcion" />
					<xsl:variable name="etiqueta">
						<xsl:choose>
							<xsl:when test="count(//categoria[@id=$variable])>0">
								<xsl:value-of select="//categoria[@id=$variable]/segmento[@valor=$opcion]/@etiqueta" />
							</xsl:when>
							<xsl:when test="count(//item[@id=$variable][opcion])>0">
								<xsl:value-of select="//item[@id=$variable]/opcion[@cod=$opcion]/@etiqueta" />
							</xsl:when>
							<xsl:when test="count(//item[@id=$variable][substring(@forma, 1, 7)='escala_'])>0">
								<xsl:variable name="escala" select="//item[@id=$variable]/@forma" />
								<xsl:value-of select="//escala[@id=$escala]/opcion[@cod=$opcion]/@etiqueta" />
							</xsl:when>
							<xsl:when test="$variable=$campo_grupo">
								<xsl:value-of select="//participante[@cod=$opcion]/@etiqueta" />
							</xsl:when>
							<xsl:otherwise><xsl:value-of select="$opcion" /></xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<tr>
						<td><xsl:value-of select="$etiqueta" /></td>
						<td class="total"><xsl:value-of select="format-number( number($fila/total), '#.##0')" /></td>
						<td class="valor"><xsl:value-of select="format-number( $fila/porcentaje div 100, '#.##0%')" /></td>
						<xsl:for-each select="$columnas/segmento[contains( concat(',',$visibles,','), concat(',',@valor,',') )]">
							<xsl:variable name="id" select="@id" />
							<xsl:variable name="cuenta" select="number( $fila/*[name()=concat('c',$id)] )" />
							<xsl:variable name="porcen" select="number( $fila/*[name()=concat('p',$id)] )" />
							<xsl:variable name="mostrar_cuenta">
								<xsl:choose>
									<xsl:when test="$cuenta > 0"><xsl:value-of select="format-number( $cuenta, '#.##0')" /></xsl:when>
									<xsl:otherwise>-</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
							<xsl:variable name="mostrar_porcen">
								<xsl:choose>
									<xsl:when test="$cuenta > 0"><xsl:value-of select="format-number( $porcen div 100, '#.##0%')" /></xsl:when>
									<xsl:otherwise>-</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
							<td class="valor"><xsl:value-of select="$mostrar_cuenta" /></td>
							<td class="valor"><xsl:value-of select="$mostrar_porcen" /></td>
						</xsl:for-each>
					</tr>
				</xsl:for-each>
				<tr>
					<td class="etiqueta">((TOTALES))&#160;&#160;</td>
					<xsl:variable name="total" select="sum($datos/total)" />
					<td colspan="2" class="separador"><xsl:value-of select="format-number( $total, '#.##0')" /></td>
					<xsl:for-each select="$columnas/segmento[contains( concat(',',$visibles,','), concat(',',@valor,',') )]">
						<xsl:variable name="id" select="@id" />
						<xsl:variable name="subtotal" select="sum( $datos/*[name()=concat('c',$id)] )" />
						<td class="total"><xsl:value-of select="format-number( $subtotal, '#.##0')" /></td>
						<td class="calculo"><xsl:value-of select="format-number( $subtotal div $total, '#.##0%')" /></td>
					</xsl:for-each>
				</tr>
			</table>
		</xsl:if>
	</xsl:template>

	<xsl:template name="tablas_frecuencias">
		<xsl:if test="count(//cuestionario/categoria[@etiqueta!=''])>0">
		<xsl:for-each select="//cuestionario/categoria">
			<xsl:variable name="campo" select="@id" />
			<xsl:variable name="etiqueta" select="@etiqueta" />
			<h4 class="titulo-tabla"><xsl:value-of select="$etiqueta" /></h4>
			<table class="reportes">
				<thead>
					<tr>
						<th width="40%">((Respuesta))</th>
						<th width="10%">((Cuenta))</th>
						<th width="12%">((Porcentaje))</th>
						<th width="38%">((Grafica))</th>
					</tr>
				</thead>
				<tbody>
					<xsl:for-each select="//cuestionario/categoria[@id=$campo]/segmento">
						<xsl:variable name="texto" select="@etiqueta" />
						<xsl:variable name="valor" select="@valor" />
						<xsl:variable name="fila" select="//resultados[@grupo='recuentos']/elemento[item=$campo][opcion=$valor]" />
						<xsl:variable name="cuenta" select="$fila/total" />
						<xsl:variable name="porcen" select="$fila/porcentaje" />
						<xsl:if test="$cuenta > 0">
							<tr>
								<td><xsl:value-of select="$texto" /></td>
								<td class="text-center"><xsl:value-of select="$cuenta" /></td>
								<td class="text-center"><xsl:value-of select="format-number(number($porcen), '#.##0,0')" />%</td>
								<td>
									<xsl:choose>
										<xsl:when test="$vista='pdf'">
											<img src="{//entorno/M_SERVIDOR}/webme/img/v.png" class="img-grafico" style="width:{format-number($porcen * 2.7, '###0')}px;" />
										</xsl:when>
										<xsl:otherwise>
											<img src="/webme/img/v.png" class="img-grafico" style="width:{format-number(number($porcen), '###0')}%;" />
										</xsl:otherwise>
									</xsl:choose>
								</td>
							</tr>
						</xsl:if>
					</xsl:for-each>
				</tbody>
			</table>
			<br/>
		</xsl:for-each>
		</xsl:if>
		<xsl:if test="count(//resultados[@grupo='recuentos']/elemento[item=$campo_grupo])>1">
		<h4 class="titulo-tabla">((Organizacion))</h4>
		<table class="reportes">
			<thead>
				<tr>
					<th width="40%">((Respuesta))</th>
					<th width="10%">((Cuenta))</th>
					<th width="12%">((Porcentaje))</th>
					<th width="38%">((Grafica))</th>
				</tr>
			</thead>
			<tbody>
				<xsl:for-each select="//resultados[@grupo='recuentos']/elemento[item=$campo_grupo]">
					<xsl:sort select="opcion" data-type="number"/>
					<xsl:variable name="valor" select="opcion" />
					<xsl:variable name="cuenta" select="total" />
					<xsl:variable name="porcen" select="porcentaje" />
					<xsl:variable name="texto" select="//resultados[@grupo='organizaciones']/elemento[id=$valor]/nombre" />
					<xsl:if test="$cuenta > 0">
						<tr>
							<td><xsl:value-of select="$texto" /></td>
							<td class="text-center"><xsl:value-of select="$cuenta" /></td>
							<td class="text-center"><xsl:value-of select="format-number(number($porcen), '#.##0,0')" />%</td>
							<td>
								<xsl:choose>
									<xsl:when test="$vista='pdf'">
										<img src="{//entorno/M_SERVIDOR}/webme/img/v.png" class="img-grafico" style="width:{format-number($porcen * 2.7, '###0')}px;" />
									</xsl:when>
									<xsl:otherwise>
										<img src="/webme/img/v.png" class="img-grafico" style="width:{format-number(number($porcen), '###0')}%;" />
									</xsl:otherwise>
								</xsl:choose>
							</td>
						</tr>
					</xsl:if>
				</xsl:for-each>
			</tbody>
		</table>
		<br/>
		</xsl:if>
		<xsl:for-each select="//cuestionario//seccion/grupo[item/@recuento!='']">
			<xsl:choose>
				<xsl:when test="@conjunto!=''"> 
					<xsl:variable name="etiqueta" select="@recuento" />
					<h4 class="titulo-tabla"><xsl:value-of select="$etiqueta" /></h4>
					<table class="reportes">
						<thead>
							<tr>
								<th width="40%">((Respuesta))</th>
								<th width="10%">((Cuenta))</th>
								<th width="12%">((Porcentaje))</th>
								<th width="38%">((Grafica))</th>
							</tr>
						</thead>
						<tbody>
							<xsl:for-each select="./item[@recuento!='']">
								<xsl:variable name="campo" select="@id" />
								<xsl:variable name="texto" select="@recuento" />
								<xsl:variable name="fila" select="//resultados[@grupo='recuentos']/elemento[item=$campo]" />
								<xsl:variable name="cuenta" select="$fila/total" />
								<xsl:variable name="porcen" select="$cuenta div $total_respuestas * 100" />
								<xsl:if test="$cuenta > 0">
									<tr>
										<td><xsl:value-of select="$texto" /></td>
										<td class="text-center"><xsl:value-of select="$cuenta" /></td>
										<td class="text-center"><xsl:value-of select="format-number($porcen, '#.##0,0')" />%</td>
										<td>
											<xsl:choose>
												<xsl:when test="$vista='pdf'">
													<img src="{//entorno/M_SERVIDOR}/webme/img/n.png" class="img-grafico" style="width:{format-number($porcen * 2.7, '###0')}px;" />
												</xsl:when>
												<xsl:otherwise>
													<img src="/webme/img/n.png" class="img-grafico" style="width:{format-number($porcen, '###0')}%;" />
												</xsl:otherwise>
											</xsl:choose>
										</td>
									</tr>
								</xsl:if>
							</xsl:for-each>
						</tbody>
					</table>
					<br/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:for-each select="./item[@recuento!='']">
						<xsl:variable name="campo" select="@id" />
						<xsl:variable name="forma" select="@forma" />
						<xsl:variable name="etiqueta" select="@recuento" />
						<h4 class="titulo-tabla"><xsl:value-of select="$etiqueta" /></h4>
						<table class="reportes">
							<thead>
								<tr>
									<th width="40%">((Respuesta))</th>
									<th width="10%">((Cuenta))</th>
									<th width="12%">((Porcentaje))</th>
									<th width="38%">((Grafica))</th>
								</tr>
							</thead>
							<tbody>
								<xsl:choose>
									<xsl:when test="substring($forma,1,7)='escala_'">
										<xsl:for-each select="//cuestionario/escala[@id=$forma]/opcion">
											<xsl:variable name="texto" select="@etiqueta" />
											<xsl:variable name="codigo" select="@cod" />
											<xsl:variable name="fila" select="//resultados[@grupo='recuentos']/elemento[item=$campo][opcion=$codigo]" />
											<xsl:variable name="cuenta" select="$fila/total" />
											<xsl:variable name="porcen" select="$fila/porcentaje" />
											<xsl:if test="$cuenta > 0">
												<tr>
													<td><xsl:value-of select="$texto" /></td>
													<td class="text-center"><xsl:value-of select="$cuenta" /></td>
													<td class="text-center"><xsl:value-of select="format-number(number($porcen), '#.##0,0')" />%</td>
													<td>
														<xsl:choose>
															<xsl:when test="$vista='pdf'">
																<img src="{//entorno/M_SERVIDOR}/webme/img/n.png" class="img-grafico" style="width:{format-number($porcen * 2.7, '###0')}px;" />
															</xsl:when>
															<xsl:otherwise>
																<img src="/webme/img/n.png" class="img-grafico" style="width:{format-number(number($porcen), '###0')}%;" />
															</xsl:otherwise>
														</xsl:choose>
													</td>
												</tr>
											</xsl:if>
										</xsl:for-each>
									</xsl:when>
									<xsl:when test="$forma='alternativas_elegir'">
										<xsl:for-each select="//cuestionario//item[@id=$campo]/opcion">
											<xsl:variable name="texto" select="@etiqueta" />
											<xsl:variable name="codigo" select="@cod" />
											<xsl:variable name="fila" select="//resultados[@grupo='recuentos']/elemento[item=$campo][opcion=$codigo]" />
											<xsl:variable name="cuenta" select="$fila/total" />
											<xsl:variable name="porcen" select="$fila/porcentaje" />
											<xsl:if test="$cuenta > 0">
												<tr>
													<td><xsl:value-of select="$texto" /></td>
													<td class="text-center"><xsl:value-of select="$cuenta" /></td>
													<td class="text-center"><xsl:value-of select="format-number(number($porcen), '#.##0,0')" />%</td>
													<td>
														<xsl:choose>
															<xsl:when test="$vista='pdf'">
																<img src="{//entorno/M_SERVIDOR}/webme/img/n.png" class="img-grafico" style="width:{format-number($porcen * 2.7, '###0')}px;" />
															</xsl:when>
															<xsl:otherwise>
																<img src="/webme/img/n.png" class="img-grafico" style="width:{format-number(number($porcen), '###0')}%;" />
															</xsl:otherwise>
														</xsl:choose>
													</td>
												</tr>
											</xsl:if>
										</xsl:for-each>
									</xsl:when>
									<xsl:otherwise>
										<xsl:for-each select="//resultados[@grupo='recuentos']/elemento[item=$campo]">
											<xsl:sort select="opcion" data-type="number"/>
											<xsl:variable name="texto" select="opcion" />
											<xsl:variable name="codigo" select="opcion" />
											<xsl:variable name="cuenta" select="total" />
											<xsl:variable name="porcen" select="porcentaje" />
											<xsl:if test="$cuenta > 0">
												<tr>
													<td><xsl:value-of select="$texto" /></td>
													<td class="text-center"><xsl:value-of select="$cuenta" /></td>
													<td class="text-center"><xsl:value-of select="format-number(number($porcen), '#.##0,0')" />%</td>
													<td>
														<xsl:choose>
															<xsl:when test="$vista='pdf'">
																<img src="{//entorno/M_SERVIDOR}/webme/img/n.png" class="img-grafico" style="width:{format-number($porcen * 2.7, '###0')}px;" />
															</xsl:when>
															<xsl:otherwise>
																<img src="/webme/img/n.png" class="img-grafico" style="width:{format-number(number($porcen), '###0')}%;" />
															</xsl:otherwise>
														</xsl:choose>
													</td>
												</tr>
											</xsl:if>
										</xsl:for-each>
									</xsl:otherwise>
								</xsl:choose>
							</tbody>
							<tfoot>
								<tr>
									<td class="text-right pie">((Total))</td>
									<td class="text-center pie"><xsl:value-of select="sum(//resultados[@grupo='recuentos']/elemento[item=$campo]/total)" /></td>
									<td class="text-center pie" colspan="2"></td>
								</tr>
							</tfoot>
						</table>
					</xsl:for-each>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>
	</xsl:template>

	<xsl:template name="diccionario_datos">
		<h4 class="titulo-tabla">((Items del Cuestionario))</h4>
		<table class="informes">
			<thead>
				<tr>
					<th>((Campo))</th>
					<th>((Etiqueta))</th>
					<th>((Req))</th>
					<th>((Procesamiento))</th>
					<th style="width:50%">((Opciones de respuesta))</th>
				</tr>
			</thead>
			<tbody>
			<xsl:for-each select="//seccion/grupo/item">
				<xsl:variable name="item" select="." />
				<xsl:variable name="requerido">
					<xsl:choose>
						<xsl:when test="@requerido='1'">SI</xsl:when>
						<xsl:otherwise>-</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="recuento">
					<xsl:choose>
						<xsl:when test="string-length(@recuento)>0">((cuenta))</xsl:when>
						<xsl:otherwise></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="calculo">
					<xsl:choose>
						<xsl:when test="string-length(@calculo)>0">((calculo))</xsl:when>
						<xsl:otherwise></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="etiqueta">
					<xsl:choose>
						<xsl:when test="string-length(@recuento)>0"><xsl:value-of select="@recuento" /></xsl:when>
						<xsl:otherwise><xsl:value-of select="@convertir" /></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="grupo">
					<xsl:if test="string-length(parent::*/@id)>0"><xsl:value-of select="parent::*/@id" /></xsl:if>
				</xsl:variable>
				<tr>
					<td class="valor"><xsl:value-of select="@id" /></td>
					<td><xsl:value-of select="$etiqueta" /></td>
					<td class="valor"><xsl:value-of select="$requerido" /></td>
					<td class="valor"><xsl:value-of select="$recuento" />
					<xsl:if test="string-length($calculo)>0"> | <xsl:value-of select="$calculo" /></xsl:if>
					</td>
					<td>
						<xsl:choose>
							<xsl:when test="count($item/opcion)>0">
								<xsl:for-each select="$item/opcion">
									<xsl:choose>
										<xsl:when test="@cod=@etiqueta"><xsl:value-of select="@cod" /></xsl:when>
										<xsl:otherwise><xsl:value-of select="concat(@cod,' = ',@etiqueta)" /></xsl:otherwise>
									</xsl:choose>
									<xsl:if test="position() != last()"><br/></xsl:if>
								</xsl:for-each>
							</xsl:when>
							<xsl:when test="substring($item/@forma,1,7)='escala_'">
								((Escala)): <xsl:value-of select="substring($item/@forma,8,50)" />
							</xsl:when>
						</xsl:choose>
					</td>
				</tr>
			</xsl:for-each>
			</tbody>
		</table>
		<xsl:if test="count(//categoria)>0">
			<br/>
			<h4 class="titulo-tabla">((Categorías de segmentación))</h4>
			<table class="informes">
				<thead>
					<tr>
						<th>((Campo))</th>
						<th>((Etiqueta))</th>
						<th style="width:50%">((Opciones de codificación))</th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="//categoria">
					<tr>
						<td class="valor"><xsl:value-of select="@id" /></td>
						<td><xsl:value-of select="@etiqueta" /></td>
						<td>
							<xsl:for-each select="segmento">
								<xsl:choose>
									<xsl:when test="@valor=@etiqueta"><xsl:value-of select="@valor" /></xsl:when>
									<xsl:otherwise><xsl:value-of select="concat(@valor,' = ',@etiqueta)" /></xsl:otherwise>
								</xsl:choose>
								<xsl:if test="position() != last()"><br/></xsl:if>
							</xsl:for-each>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table>
		</xsl:if>
		<xsl:if test="count(//indicador)>0">
			<br/>
			<h4 class="titulo-tabla">((Indicadores cuantitativos))</h4>
			<table class="informes">
				<thead>
					<tr>
						<th>((Campo))</th>
						<th>((ID))</th>
						<th>((Etiqueta))</th>
						<th style="width:50%">((Opciones de evaluación))</th>
					</tr>
				</thead>
				<tbody>
				<xsl:for-each select="//indicador">
					<tr>
						<td class="valor"><xsl:value-of select="@origen" /></td>
						<td class="valor"><xsl:value-of select="@id" /></td>
						<td><xsl:value-of select="@nombre" /></td>
						<td>
							<xsl:for-each select="semaforo">
								<xsl:value-of select="concat(@id,' = ',@desde,' - ',@hasta)" />
								<xsl:if test="position() != last()"><br/></xsl:if>
							</xsl:for-each>
						</td>
					</tr>
				</xsl:for-each>
				</tbody>
			</table>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>