<?php

namespace GDE;

use Doctrine\ORM\Mapping as ORM;

/**
 * Curriculo
 *
 * @ORM\Table(
 *   name="gde_curriculos",
 *   indexes={@ORM\Index(name="curso_modalidade_catalogo", columns={"id_curso", "id_modalidade", "catalogo"})}
 * )
 * @ORM\Entity
 */
class Curriculo extends Base {
	/**
	 * @var integer
	 *
	 * @ORM\Column(type="integer", options={"unsigned"=true}, nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	protected $id_curriculo;

	/**
	 * @var Curso
	 *
	 * @ORM\ManyToOne(targetEntity="Curso")
	 * @ORM\JoinColumn(name="id_curso", referencedColumnName="id_curso")
	 */
	protected $curso;

	/**
	 * @var Modalidade
	 *
	 * @ORM\ManyToOne(targetEntity="Modalidade")
	 * @ORM\JoinColumn(name="id_modalidade", referencedColumnName="id_modalidade")
	 */
	protected $modalidade;

	/**
	 * @var Disciplina
	 *
	 * Nem sempre estara preenchida pois existem disciplinas do curriculo que nao temos em nosso DB
	 * Inclusive "ELET." e "LING."
	 *
	 * @ORM\ManyToOne(targetEntity="Disciplina")
	 * @ORM\JoinColumn(name="id_disciplina", referencedColumnName="id_disciplina")
	 */
	protected $disciplina;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="smallint", nullable=false)
	 */
	protected $catalogo;

	/**
	 * @var string
	 *
	 * Nao utilizamos uma relation com disciplina aqui pois existem disciplinas do curriculo que nao temos em nosso DB
	 * Inclusive "ELET." e "LING."
	 *
	 * @ORM\Column(type="string", length=5, nullable=false)
	 */
	protected $sigla;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="smallint", options={"unsigned"=true}, nullable=false)
	 */
	protected $semestre;

	/**
	 * @param $param
	 * @return Curriculo[]
	 */
	public static function Consultar($param) {
		$dql = 'SELECT C FROM '.get_class().' C INNER JOIN C.curso U ';
		if(!empty($param['modalidade']))
			$dql .= 'INNER JOIN C.modalidade M ';
		if($param['curso'] == 51) {
			$dql .= 'WHERE U.numero = 28 AND C.semestre < 4 ';
			unset($param['curso'], $param['modalidade']);
		} else
			$dql .= 'WHERE U.numero = :curso ';
		if(empty($param['modalidade'])) {
			$dql .= 'AND C.modalidade IS NULL ';
			unset($param['modalidade']);
		} else
			$dql .= 'AND M.sigla = :modalidade ';
		$dql .= 'AND C.catalogo = :catalogo ';
		$dql .= 'ORDER BY C.semestre ASC';
		$query = self::_EM()->createQuery($dql)->setParameters($param);
		if((!defined('FORCE_NO_CACHE')) && (defined('CONFIG_RESULT_CACHE')) && (CONFIG_RESULT_CACHE === true) && (RESULT_CACHE_AVAILABLE === true))
			$query->useResultCache(true, CONFIG_RESULT_CACHE_TTL);
		return $query->getResult();
	}

	/**
	 * @param $curso
	 * @param $modalidade
	 * @param $catalogo
	 * @return bool
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public static function Existe($curso, $modalidade, $catalogo) {
		// Se for cursao, utilizar o curriculo da matematica aplicada
		if($curso == 51) {
			// ToDo: Fazer isso de uma forma melhor
			$curso = 28;
			$modalidade = null;
		}
		$dql = 'SELECT COUNT(C) FROM '.get_class().' C INNER JOIN C.curso U ';
		if($modalidade != null)
			$dql .= 'INNER JOIN C.modalidade M ';
		$dql .= 'WHERE U.numero = ?1 AND C.catalogo = ?2 ';
		if($modalidade != null)
			$dql .= 'AND M.sigla = ?3';
		else
			$dql .= 'AND C.modalidade IS NULL';
		$query = self::_EM()->createQuery($dql);
		$query->setParameter(1, $curso);
		$query->setParameter(2, $catalogo);
		if($modalidade != null)
			$query->setParameter(3, $modalidade);
		if((!defined('FORCE_NO_CACHE')) && (defined('CONFIG_RESULT_CACHE')) && (CONFIG_RESULT_CACHE === true) && (RESULT_CACHE_AVAILABLE === true))
			$query->useResultCache(true, CONFIG_RESULT_CACHE_TTL);
		return ($query->getSingleScalarResult() > 0);
	}

	/**
	 * @param bool $vazio
	 * @return Disciplina|null
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function getDisciplina($vazio = true) {
		$inicial = strtolower(substr($this->getSigla(false), 0, 4));
		if(($inicial == 'elet') || ($inicial == 'ling')) {
			$Disciplina = new Disciplina();
			$Disciplina->markReadOnly();
			$Disciplina->setSigla($this->getSigla(false));
			return $Disciplina;
		}

		if(parent::getDisciplina(false) !== null) {
			return parent::getDisciplina();
		}

		$Disciplina = Disciplina::Por_Sigla($this->getSigla(false), Disciplina::$NIVEIS_GRAD, $vazio);

		if((parent::getDisciplina(false) === null) && ($Disciplina->getID() != null)) {
			$this->setDisciplina($Disciplina);
			$this->Save(false);
			self::_EM()->flush($this);
		}

		if(($vazio === true) && ($Disciplina->getID() == null)) {
			$Disciplina->setSigla($this->getSigla(false));
		}

		return $Disciplina;
	}

}
