<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="content[@class='DatabaseView']">
<div class="table-responsive">
	<xsl:element name="table">
		<xsl:attribute name="class">
			table table-striped
			<xsl:if test="@nomargin">m-0</xsl:if>
			<xsl:if test="@small">table-sm</xsl:if>
			<xsl:if test="@hover">table-hover</xsl:if>
		</xsl:attribute>
		<caption><xsl:value-of select="table"/> (<xsl:value-of select="count"/> rows)</caption>
		<thead class="thead-light">
			<tr>
			<xsl:for-each select="row[1]/*">
				<th><xsl:value-of select="name()"/></th>
			</xsl:for-each>
			</tr>
		</thead>
		<tbody>
		<xsl:for-each select="row">
			<tr>
			<xsl:for-each select="*">
				<td><xsl:value-of select="."/></td>
			</xsl:for-each>
			</tr>
		</xsl:for-each>
		</tbody>
	</xsl:element>
</div>
</xsl:template>

</xsl:stylesheet>