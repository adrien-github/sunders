CREATE TABLE statistics (
  id BIGINT PRIMARY KEY,
  ts VARCHAR(20),
  year SMALLINT,
  month TINYINT,
  day TINYINT,
  t TIME,
  week TINYINT,
  version INT
);

INSERT INTO statistics (
	id,
	ts,
	year,
	month,
	day,
	t,
	week,
	version)
SELECT
  t1.id,
  t1.v,
  YEAR(STR_TO_DATE(t1.v,'%Y-%m-%dT%H:%i:%sZ')),
  MONTH(STR_TO_DATE(t1.v,'%Y-%m-%dT%H:%i:%sZ')),
  DAYOFMONTH(STR_TO_DATE(t1.v,'%Y-%m-%dT%H:%i:%sZ')),
  TIME(STR_TO_DATE(t1.v,'%Y-%m-%dT%H:%i:%sZ')),
  WEEKOFYEAR(STR_TO_DATE(t1.v,'%Y-%m-%dT%H:%i:%sZ')),
  t2.v
FROM tag AS t1
INNER JOIN tag AS t2
ON t2.id = t1.id AND t2.k = 'version'
WHERE t1.k = 'timestamp'
