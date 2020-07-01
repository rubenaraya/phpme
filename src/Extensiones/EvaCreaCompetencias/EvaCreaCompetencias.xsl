<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="utf-8" indent="yes" />
		<xsl:param name="info" />

	<xsl:template match="/">
		<xsl:choose>
			<xsl:when test="$info='realizar'">
				<xsl:call-template name="vista_realizar" />
			</xsl:when>
			<xsl:when test="$info='avance'">
				<xsl:call-template name="vista_avance" />
			</xsl:when>
			<xsl:when test="$info='resulta'">
				<xsl:call-template name="vista_resultados" />
			</xsl:when>
			<xsl:when test="$info='informe'">
				<xsl:call-template name="vista_informe" />
			</xsl:when>
			<xsl:when test="$info='previa'">
				<xsl:call-template name="vista_previa" />
			</xsl:when>
			<xsl:when test="$info='exporta'">
				<xsl:call-template name="vista_exporta" />
			</xsl:when>
		</xsl:choose>
    </xsl:template>

	<xsl:template name="vista_realizar">
		Aquí va el Cuestionario
		<xsl:call-template name="funciones_instructor" />
		<xsl:call-template name="funciones_extension" />
    </xsl:template>
	<xsl:template name="vista_avance">
		vista_avance
    </xsl:template>
	<xsl:template name="vista_resultados">
		vista_resultados
    </xsl:template>
	<xsl:template name="vista_informe">
		vista_informe
    </xsl:template>
	<xsl:template name="vista_previa">
		vista_previa
    </xsl:template>
	<xsl:template name="vista_exporta">
		vista_exporta
    </xsl:template>

	<xsl:template name="funciones_extension">
		<script type="text/javascript">
		//<![CDATA[
		//TODO: Faltan funciones para el control de flujos entre secciones, usando atributos especiales
		//TODO: Probar sobreescribir la funcion validarItem con reglas propias
		//ME_Instructor.prototype.validarItem = function( id, marcar ) {};
		ME_Instructor.prototype.xxxxx = function() {};
		//]]>
		</script>
	</xsl:template>

	<xsl:template name="cuestionario_web">
		<xsl:for-each select="//cuestionario/seccion">
			<xsl:variable name="sec" select="position()" />
			<div class="row">
				<div class="col-12 panel panel-default" style="padding-top: 0.5em; padding-bottom: 0.5em">
					<xsl:if test="string-length(@titulo)>0">
						<h3><xsl:value-of select="@titulo" /></h3>
					</xsl:if>
					<xsl:if test="string-length(@cuestionario)>0">
						<p style="text-align: justify;"><xsl:value-of select="@cuestionario" /></p>
					</xsl:if>
					<xsl:for-each select="grupo[not(@forma)]">
						<xsl:variable name="gru" select="position()" />
						<xsl:choose>
							<xsl:when test="string-length(@conjunto)>0">
								<div class="row">
									<div class="col-12">
										<xsl:if test="string-length(@titulo)>0">
											<p><big><i><xsl:value-of select="@titulo" /></i></big></p>
										</xsl:if>
										<xsl:if test="string-length(@texto)>0">
											<p style="text-align: justify;"><xsl:value-of select="@texto" /></p>
										</xsl:if>
									</div>
									<div class="col-sm-12 col-md-6 col-lg-6">
										<div class="m-xs p-xs b-r-lg me-borde me-suave me-item" id="item_{$sec}_{$gru}">
											<p><xsl:value-of select="@conjunto" /></p>
											<xsl:for-each select="item[@forma!='salto']">
												<div class="rbo">
													<input type="checkbox" value="1" name="{@id}" id="{@id}" class="{$sec}_{$gru}" onclick="M.Cuestionario.elegirVarios('{$sec}_{$gru}', 'item_{$sec}_{$gru}')" />
													<label for="{@id}">&#160;<xsl:value-of select="@enunciado" />&#160;</label>
												</div>
											</xsl:for-each>
										</div>
									</div>
								</div>
							</xsl:when>
							<xsl:otherwise>
								<div class="row">
									<div class="col-12">
										<xsl:if test="string-length(@titulo)>0 or string-length(@texto)>0">
											<hr/>
										</xsl:if>
										<xsl:if test="string-length(@titulo)>0">
											<p><big><i><xsl:value-of select="@titulo" /></i></big></p>
										</xsl:if>
										<xsl:if test="string-length(@texto)>0">
											<p style="text-align: justify;"><xsl:value-of select="@texto" /></p>
										</xsl:if>
									</div>
									<xsl:for-each select="item[@forma!='salto']">
										<xsl:variable name="item" select="." />
										<xsl:variable name="id" select="$item/@id" />
										<div>
											<xsl:attribute name="class">
												<xsl:choose>
													<xsl:when test="$item/@ancho='XL'">col-12</xsl:when>
													<xsl:when test="$item/@ancho='L'">col-sm-12 col-md-6</xsl:when>
													<xsl:when test="$item/@ancho='M'">col-sm-12 col-md-6 col-lg-4</xsl:when>
													<xsl:when test="$item/@ancho='S'">col-sm-6 col-md-4 col-lg-3</xsl:when>
													<xsl:when test="$item/@ancho='XS'">col-sm-4 col-md-3 col-lg-2</xsl:when>
													<xsl:otherwise>col-sm-12 col-md-6 col-lg-4</xsl:otherwise>
												</xsl:choose>
											</xsl:attribute>
											<div id="item_{$id}">
												<xsl:attribute name="class">
													<xsl:choose>
														<xsl:when test="$item/@requerido='1'">m-xs p-xs b-r-lg me-borde me-suave me-requerido me-item</xsl:when>
														<xsl:otherwise>m-xs p-xs b-r-lg me-borde me-suave me-item</xsl:otherwise>
													</xsl:choose>
												</xsl:attribute>
												<xsl:call-template name="campo_web">
													<xsl:with-param name="item" select="$item" />
												</xsl:call-template>
											</div>
										</div>
									</xsl:for-each>
								</div>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</div>
			</div>
		</xsl:for-each>
	</xsl:template>

	<xsl:template name="cuestionario_pdf">
		<xsl:for-each select="//cuestionario/seccion">
			<div>
				<xsl:if test="string-length(@titulo)>0">
					<h3><xsl:value-of select="@titulo" /></h3>
				</xsl:if>
				<xsl:if test="string-length(@cuestionario)>0">
					<div class="text-justify"><xsl:value-of select="@cuestionario" /></div><br/>
				</xsl:if>
				<xsl:for-each select="grupo">
					<xsl:choose>
						<xsl:when test="@forma='salto'">
							<pagebreak />
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="string-length(@conjunto)>0">
									<div>
										<xsl:if test="string-length(@titulo)>0">
											<div class="tit_grupo"><xsl:value-of select="@titulo" /></div>
										</xsl:if>
										<xsl:if test="string-length(@texto)>0">
											<div class="txt_grupo"><xsl:value-of select="@texto" /></div>
										</xsl:if>
										<p><xsl:value-of select="@conjunto" /></p>
										<xsl:for-each select="item[@forma!='salto']">
											<div><span class="cuadro">&#160;&#160;&#160;&#160;&#160;</span>&#160; <xsl:value-of select="@enunciado" /></div>
										</xsl:for-each>
									</div>
								</xsl:when>
								<xsl:otherwise>
									<div>
										<xsl:if test="string-length(@titulo)>0">
											<div class="tit_grupo"><xsl:value-of select="@titulo" /></div>
										</xsl:if>
										<xsl:if test="string-length(@texto)>0">
											<div class="txt_grupo"><xsl:value-of select="@texto" /></div>
										</xsl:if>
										<xsl:for-each select="item">
											<xsl:choose>
												<xsl:when test="@forma='salto'">
													<pagebreak />
												</xsl:when>
												<xsl:otherwise>
													<xsl:call-template name="campo_pdf">
														<xsl:with-param name="item" select="." />
													</xsl:call-template>
												</xsl:otherwise>
											</xsl:choose>
										</xsl:for-each>
									</div>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:for-each>
			</div>
		</xsl:for-each>
	</xsl:template>

</xsl:stylesheet>