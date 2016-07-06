# -*- coding: utf-8 -*-

__author__ = 'Asger Bachmann (agb@birc.au.dk)'


class SquarifiedTreemap(object):
    class Rectangle(object):
        def __init__(self, x, y, w, h):
            self.x = x
            self.y = y
            self.w = w
            self.h = h
            self.rows = []

        def width(self):
            return min(self.w, self.h)

        def dimensions(self):
            return self.w, self.h

        def area(self):
            return self.w * self.h

        def layout_row(self, row):
            x1 = self.x
            y1 = self.y
            x2 = x1 + self.w
            y2 = y1 + self.h

            if self.h < self.w:
                row_height = sum(row) / float(self.h)
                if x1 + row_height >= x2:
                    row_height = x2 - x1

                for (r, i) in zip(row, range(len(row))):
                    row_width = r / float(row_height)
                    if y1 + row_width > y2 or i + 1 == len(row):
                        row_width = y2 - y1

                    self.rows.append(self.__class__(x1, y1, row_height, row_width))
                    y1 += row_width

                self.x += row_height
                self.w -= row_height
            else:
                row_height = sum(row) / float(self.w)
                if y1 + row_height >= y2:
                    row_height = y2 - y1

                for (r, i) in zip(row, range(len(row))):
                    row_width = r / float(row_height)
                    if x1 + row_width > x2 or i + 1 == len(row):
                        row_width = x2 - x1

                    self.rows.append(self.__class__(x1, y1, row_width, row_height))
                    x1 += row_width

                self.y += row_height
                self.h -= row_height

    @staticmethod
    def worst(row, w):
        if len(row) == 0:
            return float('inf')

        max_r = max(row)
        min_r = min(row)
        w_sq = w ** 2
        sum_sq = sum(row) ** 2
        return max(w_sq * max_r / sum_sq, sum_sq / (w_sq * min_r))

    @staticmethod
    def squarify_recursive(children, row, w, container):
        if len(children) == 0:
            container.layout_row(row)
            return

        c = children[0]
        extended_row = row[:]  # Copy the row.
        extended_row.append(c)
        # This inequality is reversed compared to the original algorithm.
        # This produces better results with more square-like rectangles compared to elongated rectangles.
        if SquarifiedTreemap.worst(row, w) > SquarifiedTreemap.worst(extended_row, w):
            SquarifiedTreemap.squarify_recursive(children[1:], extended_row, w, container)
        else:
            container.layout_row(row)
            SquarifiedTreemap.squarify_recursive(children, [], container.width(), container)

    @staticmethod
    def squarify_iterative(children, container):
        w = container.width()
        row = []
        while len(children) != 0:
            c = children[0]
            extended_row = row[:]
            extended_row.append(c)

            if SquarifiedTreemap.worst(row, w) > SquarifiedTreemap.worst(extended_row, w):
                children = children[1:]
                row = extended_row
            else:
                container.layout_row(row)
                row = []
                w = container.width()

        container.layout_row(row)

    @staticmethod
    def normalize_input(data, area):
        data_sum = sum(data)
        multiplier = float(area) / data_sum
        return [d * multiplier for d in data]

    def __init__(self, children, x=0, w=1, y=0, h=1):
        container = SquarifiedTreemap.Rectangle(x, y, w, h)
        data = SquarifiedTreemap.normalize_input(children, w * h)
        # SquarifiedTreemap.squarify_iterative(data, [], container.width(), container)
        SquarifiedTreemap.squarify_iterative(data, container)
        self.layout = container.rows
